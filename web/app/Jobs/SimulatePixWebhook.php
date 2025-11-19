<?php

namespace App\Jobs;

use App\Models\Pix;
use App\Services\SubadquirenteServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SimulatePixWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * PIX que será processado
     *
     * @var Pix
     */
    protected Pix $pix;

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
     * @param Pix $pix
     */
    public function __construct(Pix $pix)
    {
        $this->pix = $pix;
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
            Log::info('SimulatePixWebhook: Iniciando simulação', [
                'pix_id' => $this->pix->id,
                'external_id' => $this->pix->external_id,
                'delay' => $this->delaySeconds,
            ]);

            // Recarrega o PIX do banco para garantir dados atualizados
            $this->pix->refresh();

            // Verifica se o PIX ainda está pendente
            if (!$this->pix->isPending()) {
                Log::info('SimulatePixWebhook: PIX já foi processado, cancelando simulação', [
                    'pix_id' => $this->pix->id,
                    'status' => $this->pix->status,
                ]);
                return;
            }

            // Busca a subadquirente
            $subadquirente = SubadquirenteServiceFactory::make($this->pix->subadquirente);

            // Gera payload de webhook conforme formato da subadquirente
            $payload = $this->generateWebhookPayload();

            // Processa o webhook através da subadquirente
            $subadquirente->processWebhook($payload, 'pix');

            Log::info('SimulatePixWebhook: Webhook simulado processado com sucesso', [
                'pix_id' => $this->pix->id,
                'external_id' => $this->pix->external_id,
            ]);
        } catch (\Exception $e) {
            Log::error('SimulatePixWebhook: Erro ao simular webhook', [
                'pix_id' => $this->pix->id,
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
        $subadquirenteName = $this->pix->subadquirente->name;
        $now = now();

        // Gera payload conforme formato da subadquirente
        if ($subadquirenteName === 'SubadqA') {
            return [
                'event' => 'pix_payment_confirmed',
                'transaction_id' => $this->pix->external_id ?? 'TXN' . $this->pix->id,
                'pix_id' => 'PIX' . $this->pix->id,
                'status' => 'CONFIRMED',
                'amount' => (float) $this->pix->amount,
                'payer_name' => $this->pix->payer_name ?? 'Pagador Simulado',
                'payer_cpf' => $this->pix->payer_cpf ?? '12345678900',
                'payment_date' => $now->toIso8601String(),
                'metadata' => [
                    'source' => 'SubadqA',
                    'environment' => 'sandbox',
                    'simulated' => true,
                ],
            ];
        } elseif ($subadquirenteName === 'SubadqB') {
            return [
                'type' => 'pix.status_update',
                'data' => [
                    'id' => $this->pix->external_id ?? 'PX' . $this->pix->id,
                    'status' => 'PAID',
                    'value' => (float) $this->pix->amount,
                    'payer' => [
                        'name' => $this->pix->payer_name ?? 'Pagador Simulado',
                        'document' => $this->pix->payer_cpf ?? '98765432100',
                    ],
                    'confirmed_at' => $now->toIso8601String(),
                ],
                'signature' => bin2hex(random_bytes(6)),
            ];
        }

        // Fallback genérico
        return [
            'event' => 'pix_payment_confirmed',
            'pix_id' => $this->pix->external_id ?? 'PIX' . $this->pix->id,
            'status' => 'CONFIRMED',
            'amount' => (float) $this->pix->amount,
            'payment_date' => $now->toIso8601String(),
        ];
    }
}
