<?php

namespace App\Services;

use App\Exceptions\SubadquirenteNotFoundException;
use App\Exceptions\WebhookProcessingException;
use App\Exceptions\WithdrawCreationException;
use App\Models\User;
use App\Models\Withdraw;
use App\Repositories\WithdrawRepository;
use App\Subadquirentes\Interfaces\SubadquirenteInterface;
use Illuminate\Support\Facades\Log;

class WithdrawService
{
    protected WithdrawRepository $repository;

    public function __construct(WithdrawRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Cria um novo saque
     *
     * @param array $data Dados do saque (amount, bank_account, etc)
     * @param User $user Usuário que está criando o saque
     * @return Withdraw
     * @throws \Exception
     */
    public function createWithdraw(array $data, User $user): Withdraw
    {
        try {
            // Busca a subadquirente do usuário
            $subadquirente = SubadquirenteServiceFactory::makeFromAuthenticatedUser($user);

            // Valida dados obrigatórios
            $this->validateWithdrawData($data);

            // Chama a API da subadquirente para criar o saque
            try {
                $response = $subadquirente->createWithdraw($data);
            } catch (\Exception $e) {
                Log::error('Erro na API da subadquirente ao criar saque', [
                    'user_id' => $user->id,
                    'subadquirente' => $user->subadquirente->name ?? 'N/A',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw new WithdrawCreationException(
                    'Erro ao comunicar com a subadquirente: ' . $e->getMessage(),
                    502,
                    $e,
                    ['subadquirente' => $user->subadquirente->name ?? 'N/A']
                );
            }

            // Extrai o external_id da resposta
            $externalId = $this->extractExternalId($response);

            // Salva no banco de dados
            try {
                $withdrawData = [
                    'user_id' => $user->id,
                    'subadquirente_id' => $user->subadquirente_id,
                    'external_id' => $externalId,
                    'amount' => $data['amount'],
                    'status' => Withdraw::STATUS_PENDING,
                    'bank_account' => $data['bank_account'] ?? null,
                    'requested_at' => now(),
                    'metadata' => [
                        'api_response' => $response,
                        'created_at' => now()->toDateTimeString(),
                    ],
                ];

                $withdraw = $this->repository->create($withdrawData);
            } catch (\Exception $e) {
                Log::error('Erro ao salvar saque no banco de dados', [
                    'user_id' => $user->id,
                    'external_id' => $externalId,
                    'error' => $e->getMessage(),
                ]);
                throw new WithdrawCreationException(
                    'Erro ao salvar saque no banco de dados: ' . $e->getMessage(),
                    500,
                    $e,
                    ['external_id' => $externalId]
                );
            }

            Log::info('Saque criado com sucesso', [
                'withdraw_id' => $withdraw->id,
                'external_id' => $externalId,
                'user_id' => $user->id,
                'subadquirente' => $user->subadquirente->name,
            ]);

            // Despacha job para simular webhook após delay aleatório
            try {
                \App\Jobs\SimulateWithdrawWebhook::dispatch($withdraw);
                Log::info('Simulação de webhook de saque agendada', [
                    'withdraw_id' => $withdraw->id,
                ]);
            } catch (\Exception $e) {
                // Não falha a criação do saque se o job falhar
                Log::warning('Erro ao agendar simulação de webhook de saque', [
                    'withdraw_id' => $withdraw->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return $withdraw;
        } catch (SubadquirenteNotFoundException $e) {
            throw $e;
        } catch (WithdrawCreationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao criar saque', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);
            throw new WithdrawCreationException(
                'Erro inesperado ao criar saque: ' . $e->getMessage(),
                500,
                $e,
                ['user_id' => $user->id]
            );
        }
    }

    /**
     * Processa webhook de saque (confirmação de saque concluído)
     *
     * @param array $normalized Dados normalizados do webhook
     * @return Withdraw|null
     * @throws \Exception
     */
    public function processWebhook(array $normalized): ?Withdraw
    {
        try {
            // Busca o saque pelo external_id ou withdraw_id
            $identifier = $normalized['external_id'] ?? $normalized['withdraw_id'] ?? null;

            if (!$identifier) {
                Log::warning('WithdrawService: Webhook sem identificador válido', [
                    'normalized' => $normalized,
                ]);
                return null;
            }

            $withdraw = $this->repository->findByExternalIdOrWithdrawId($identifier);

            if (!$withdraw) {
                Log::warning('WithdrawService: Saque não encontrado para webhook', [
                    'identifier' => $identifier,
                    'normalized' => $normalized,
                ]);
                return null;
            }

            // Armazena o status anterior para log
            $oldStatus = $withdraw->status;

            // Atualiza o status e dados do saque
            $updateData = [
                'status' => $normalized['status'] ?? $withdraw->status,
            ];

            // Atualiza dados opcionais se fornecidos
            if (isset($normalized['amount'])) {
                $updateData['amount'] = $normalized['amount'];
            }

            if (isset($normalized['bank_account'])) {
                $updateData['bank_account'] = $normalized['bank_account'];
            }

            if (isset($normalized['requested_at'])) {
                $updateData['requested_at'] = $normalized['requested_at'];
            }

            if (isset($normalized['completed_at'])) {
                $updateData['completed_at'] = $normalized['completed_at'];
            }

            // Atualiza metadata mantendo dados anteriores
            $metadata = $withdraw->metadata ?? [];
            $metadata['webhook_received_at'] = now()->toDateTimeString();
            $metadata['webhook_data'] = $normalized;
            $updateData['metadata'] = $metadata;

            try {
                $this->repository->update($withdraw, $updateData);
            } catch (\Exception $e) {
                Log::error('Erro ao atualizar saque no banco de dados', [
                    'withdraw_id' => $withdraw->id,
                    'identifier' => $identifier,
                    'error' => $e->getMessage(),
                ]);
                throw new WebhookProcessingException(
                    'Erro ao atualizar saque no banco de dados: ' . $e->getMessage(),
                    500,
                    $e,
                    ['withdraw_id' => $withdraw->id, 'identifier' => $identifier]
                );
            }

            Log::info('Saque atualizado via webhook', [
                'withdraw_id' => $withdraw->id,
                'external_id' => $withdraw->external_id,
                'old_status' => $oldStatus,
                'new_status' => $updateData['status'],
            ]);

            return $withdraw->fresh();
        } catch (WebhookProcessingException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao processar webhook de saque', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'normalized' => $normalized,
            ]);
            throw new WebhookProcessingException(
                'Erro inesperado ao processar webhook de saque: ' . $e->getMessage(),
                500,
                $e,
                ['normalized' => $normalized]
            );
        }
    }

    /**
     * Valida dados obrigatórios para criar saque
     *
     * @param array $data
     * @return void
     * @throws \Exception
     */
    protected function validateWithdrawData(array $data): void
    {
        if (!isset($data['amount']) || $data['amount'] <= 0) {
            throw new WithdrawCreationException(
                'Valor do saque é obrigatório e deve ser maior que zero',
                422,
                null,
                ['field' => 'amount', 'value' => $data['amount'] ?? null]
            );
        }

        if (!isset($data['bank_account']) || !is_array($data['bank_account'])) {
            throw new WithdrawCreationException(
                'Dados bancários são obrigatórios para realizar saque',
                422,
                null,
                ['field' => 'bank_account']
            );
        }

        // Valida campos obrigatórios da conta bancária
        $requiredBankFields = ['bank_code', 'agency', 'account', 'account_type'];
        foreach ($requiredBankFields as $field) {
            if (!isset($data['bank_account'][$field]) || empty($data['bank_account'][$field])) {
                throw new WithdrawCreationException(
                    "Campo '{$field}' é obrigatório nos dados bancários",
                    422,
                    null,
                    ['field' => "bank_account.{$field}", 'bank_account' => $data['bank_account']]
                );
            }
        }
    }

    /**
     * Extrai o external_id da resposta da API
     * Tenta diferentes campos comuns nas respostas das subadquirentes
     *
     * @param array $response
     * @return string|null
     */
    protected function extractExternalId(array $response): ?string
    {
        // Tenta diferentes campos comuns
        $possibleFields = ['id', 'transaction_id', 'withdraw_id', 'external_id', 'transfer_id'];

        foreach ($possibleFields as $field) {
            if (isset($response[$field])) {
                return (string) $response[$field];
            }
        }

        // Se não encontrar, gera um ID baseado no timestamp
        Log::warning('WithdrawService: External ID não encontrado na resposta da API, gerando ID temporário', [
            'response' => $response,
        ]);

        return 'TEMP_' . time() . '_' . uniqid();
    }
}

