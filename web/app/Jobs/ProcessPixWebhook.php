<?php

namespace App\Jobs;

use App\Services\PixService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPixWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de tentativas em caso de falha
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Tempo limite em segundos
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * Dados normalizados do webhook
     *
     * @var array
     */
    protected array $normalizedData;

    /**
     * Create a new job instance.
     *
     * @param array $normalizedData Dados normalizados do webhook de PIX
     */
    public function __construct(array $normalizedData)
    {
        $this->normalizedData = $normalizedData;
    }

    /**
     * Execute the job.
     *
     * @param PixService $pixService
     * @return void
     */
    public function handle(PixService $pixService): void
    {
        try {
            Log::info('ProcessPixWebhook: Iniciando processamento', [
                'normalized_data' => $this->normalizedData,
            ]);

            $pix = $pixService->processWebhook($this->normalizedData);

            if ($pix) {
                Log::info('ProcessPixWebhook: Webhook processado com sucesso', [
                    'pix_id' => $pix->id,
                    'external_id' => $pix->external_id,
                    'status' => $pix->status,
                ]);
            } else {
                Log::warning('ProcessPixWebhook: PIX não encontrado para processar webhook', [
                    'normalized_data' => $this->normalizedData,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('ProcessPixWebhook: Erro ao processar webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'normalized_data' => $this->normalizedData,
            ]);

            // Re-lança a exceção para que o Laravel possa registrar como falha
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessPixWebhook: Job falhou após todas as tentativas', [
            'error' => $exception->getMessage(),
            'normalized_data' => $this->normalizedData,
        ]);
    }
}
