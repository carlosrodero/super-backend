<?php

namespace App\Services;

use App\Models\Pix;
use App\Models\User;
use App\Repositories\PixRepository;
use App\Subadquirentes\Interfaces\SubadquirenteInterface;
use Illuminate\Support\Facades\Log;

class PixService
{
    protected PixRepository $repository;

    public function __construct(PixRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Cria uma nova cobrança PIX
     *
     * @param array $data Dados do PIX (amount, payer_name, payer_cpf, etc)
     * @param User $user Usuário que está criando o PIX
     * @return Pix
     * @throws \Exception
     */
    public function createPix(array $data, User $user): Pix
    {
        try {
            // Busca a subadquirente do usuário
            $subadquirente = SubadquirenteServiceFactory::makeFromAuthenticatedUser($user);

            // Valida dados obrigatórios
            $this->validatePixData($data);

            // Chama a API da subadquirente para criar o PIX
            $response = $subadquirente->createPix($data);

            // Extrai o external_id da resposta
            $externalId = $this->extractExternalId($response);

            // Salva no banco de dados
            $pixData = [
                'user_id' => $user->id,
                'subadquirente_id' => $user->subadquirente_id,
                'external_id' => $externalId,
                'amount' => $data['amount'],
                'status' => Pix::STATUS_PENDING,
                'payer_name' => $data['payer_name'] ?? null,
                'payer_cpf' => $data['payer_cpf'] ?? null,
                'metadata' => [
                    'api_response' => $response,
                    'created_at' => now()->toDateTimeString(),
                ],
            ];

            $pix = $this->repository->create($pixData);

            Log::info('PIX criado com sucesso', [
                'pix_id' => $pix->id,
                'external_id' => $externalId,
                'user_id' => $user->id,
                'subadquirente' => $user->subadquirente->name,
            ]);

            // Despacha job para simular webhook após delay aleatório
            // O delay é definido dentro do próprio Job (2-10 segundos aleatórios)
            \App\Jobs\SimulatePixWebhook::dispatch($pix);

            Log::info('Simulação de webhook PIX agendada', [
                'pix_id' => $pix->id,
            ]);

            return $pix;
        } catch (\Exception $e) {
            Log::error('Erro ao criar PIX', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Processa webhook de PIX (confirmação de pagamento)
     *
     * @param array $normalized Dados normalizados do webhook
     * @return Pix|null
     * @throws \Exception
     */
    public function processWebhook(array $normalized): ?Pix
    {
        try {
            // Busca o PIX pelo external_id ou pix_id
            $identifier = $normalized['external_id'] ?? $normalized['pix_id'] ?? null;

            if (!$identifier) {
                Log::warning('PixService: Webhook sem identificador válido', [
                    'normalized' => $normalized,
                ]);
                return null;
            }

            $pix = $this->repository->findByExternalId($identifier);

            if (!$pix) {
                Log::warning('PixService: PIX não encontrado para webhook', [
                    'identifier' => $identifier,
                    'normalized' => $normalized,
                ]);
                return null;
            }

            // Armazena o status anterior para log
            $oldStatus = $pix->status;

            // Atualiza o status e dados do PIX
            $updateData = [
                'status' => $normalized['status'] ?? $pix->status,
            ];

            // Atualiza dados opcionais se fornecidos
            if (isset($normalized['amount'])) {
                $updateData['amount'] = $normalized['amount'];
            }

            if (isset($normalized['payer_name'])) {
                $updateData['payer_name'] = $normalized['payer_name'];
            }

            if (isset($normalized['payer_cpf'])) {
                $updateData['payer_cpf'] = $normalized['payer_cpf'];
            }

            if (isset($normalized['payment_date'])) {
                $updateData['payment_date'] = $normalized['payment_date'];
            }

            // Atualiza metadata mantendo dados anteriores
            $metadata = $pix->metadata ?? [];
            $metadata['webhook_received_at'] = now()->toDateTimeString();
            $metadata['webhook_data'] = $normalized;
            $updateData['metadata'] = $metadata;

            $this->repository->update($pix, $updateData);

            Log::info('PIX atualizado via webhook', [
                'pix_id' => $pix->id,
                'external_id' => $pix->external_id,
                'old_status' => $oldStatus,
                'new_status' => $updateData['status'],
            ]);

            return $pix->fresh();
        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook de PIX', [
                'error' => $e->getMessage(),
                'normalized' => $normalized,
            ]);
            throw $e;
        }
    }

    /**
     * Valida dados obrigatórios para criar PIX
     *
     * @param array $data
     * @return void
     * @throws \Exception
     */
    protected function validatePixData(array $data): void
    {
        if (!isset($data['amount']) || $data['amount'] <= 0) {
            throw new \Exception('Valor do PIX é obrigatório e deve ser maior que zero');
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
        $possibleFields = ['id', 'transaction_id', 'pix_id', 'external_id', 'charge_id'];

        foreach ($possibleFields as $field) {
            if (isset($response[$field])) {
                return (string) $response[$field];
            }
        }

        // Se não encontrar, gera um ID baseado no timestamp
        Log::warning('PixService: External ID não encontrado na resposta da API, gerando ID temporário', [
            'response' => $response,
        ]);

        return 'TEMP_' . time() . '_' . uniqid();
    }
}

