<?php

namespace App\Jobs;

use App\Models\Withdraw;
use App\Services\SubadquirenteServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SimulateWithdrawWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Saque que será processado
     *
     * @var Withdraw
     */
    protected Withdraw $withdraw;

    /**
     * Delay aleatório em segundos (entre 2 e 10 segundos)
     * Simula latência real de processamento
     *
     * @var int
     */
    protected int $delaySeconds;

    /**
     * Create a new job instance.
     *
     * @param Withdraw $withdraw
     */
    public function __construct(Withdraw $withdraw)
    {
        $this->withdraw = $withdraw;
        // Delay aleatório entre 2 e 10 segundos para simular latência real
        $this->delaySeconds = rand(2, 10);
    }

    /**
     * Define o delay do job
     *
     * @return int
     */
    public function delay(): int
    {
        return $this->delaySeconds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            Log::info('SimulateWithdrawWebhook: Iniciando simulação', [
                'withdraw_id' => $this->withdraw->id,
                'external_id' => $this->withdraw->external_id,
                'delay' => $this->delaySeconds,
            ]);

            // Recarrega o saque do banco para garantir dados atualizados
            $this->withdraw->refresh();

            // Verifica se o saque ainda está pendente
            if (!$this->withdraw->isPending()) {
                Log::info('SimulateWithdrawWebhook: Saque já foi processado, cancelando simulação', [
                    'withdraw_id' => $this->withdraw->id,
                    'status' => $this->withdraw->status,
                ]);
                return;
            }

            // Busca a subadquirente
            $subadquirente = SubadquirenteServiceFactory::make($this->withdraw->subadquirente);

            // Gera payload de webhook conforme formato da subadquirente
            $payload = $this->generateWebhookPayload();

            // Processa o webhook através da subadquirente
            $subadquirente->processWebhook($payload, 'withdraw');

            Log::info('SimulateWithdrawWebhook: Webhook simulado processado com sucesso', [
                'withdraw_id' => $this->withdraw->id,
                'external_id' => $this->withdraw->external_id,
            ]);
        } catch (\Exception $e) {
            Log::error('SimulateWithdrawWebhook: Erro ao simular webhook', [
                'withdraw_id' => $this->withdraw->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Gera payload de webhook conforme formato da subadquirente
     *
     * @return array
     */
    protected function generateWebhookPayload(): array
    {
        $subadquirenteName = $this->withdraw->subadquirente->name;
        $now = now();
        $requestedAt = $this->withdraw->requested_at ?? $now->subSeconds(rand(30, 120));

        // Gera payload conforme formato da subadquirente
        if ($subadquirenteName === 'SubadqA') {
            return [
                'event' => 'withdraw_completed',
                'withdraw_id' => 'WD' . $this->withdraw->id,
                'transaction_id' => $this->withdraw->external_id ?? 'TXN' . $this->withdraw->id,
                'status' => 'SUCCESS',
                'amount' => (float) $this->withdraw->amount,
                'requested_at' => $requestedAt->toIso8601String(),
                'completed_at' => $now->toIso8601String(),
                'metadata' => [
                    'source' => 'SubadqA',
                    'destination_bank' => $this->withdraw->bank_account['bank_code'] ?? 'N/A',
                    'simulated' => true,
                ],
            ];
        } elseif ($subadquirenteName === 'SubadqB') {
            return [
                'type' => 'withdraw.status_update',
                'data' => [
                    'id' => $this->withdraw->external_id ?? 'WDX' . $this->withdraw->id,
                    'status' => 'DONE',
                    'amount' => (float) $this->withdraw->amount,
                    'bank_account' => $this->withdraw->bank_account ?? [
                        'bank' => 'Nubank',
                        'agency' => '0001',
                        'account' => '1234567-8',
                    ],
                    'processed_at' => $now->toIso8601String(),
                ],
                'signature' => bin2hex(random_bytes(6)),
            ];
        }

        // Fallback genérico
        return [
            'event' => 'withdraw_completed',
            'withdraw_id' => $this->withdraw->external_id ?? 'WD' . $this->withdraw->id,
            'status' => 'SUCCESS',
            'amount' => (float) $this->withdraw->amount,
            'completed_at' => $now->toIso8601String(),
        ];
    }
}
