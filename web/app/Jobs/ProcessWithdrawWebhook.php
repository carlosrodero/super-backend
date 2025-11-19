<?php

namespace App\Jobs;

use App\Exceptions\WebhookProcessingException;
use App\Services\WithdrawService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWithdrawWebhook implements ShouldQueue
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
     * @param array $normalizedData Dados normalizados do webhook de saque
     */
    public function __construct(array $normalizedData)
    {
        $this->normalizedData = $normalizedData;
    }

    /**
     * Execute the job.
     *
     * @param WithdrawService $withdrawService
     * @return void
     */
    public function handle(WithdrawService $withdrawService): void
    {
        try {
            Log::info('ProcessWithdrawWebhook: Iniciando processamento', [
                'normalized_data' => $this->normalizedData,
            ]);

            $withdraw = $withdrawService->processWebhook($this->normalizedData);

            if ($withdraw) {
                Log::info('ProcessWithdrawWebhook: Webhook processado com sucesso', [
                    'withdraw_id' => $withdraw->id,
                    'external_id' => $withdraw->external_id,
                    'status' => $withdraw->status,
                ]);
            } else {
                Log::warning('ProcessWithdrawWebhook: Saque não encontrado para processar webhook', [
                    'normalized_data' => $this->normalizedData,
                ]);
            }
        } catch (WebhookProcessingException $e) {
            Log::error('ProcessWithdrawWebhook: Erro ao processar webhook', [
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
                'normalized_data' => $this->normalizedData,
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('ProcessWithdrawWebhook: Erro inesperado ao processar webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'normalized_data' => $this->normalizedData,
            ]);
            throw new WebhookProcessingException(
                'Erro inesperado ao processar webhook de saque: ' . $e->getMessage(),
                500,
                $e,
                ['normalized_data' => $this->normalizedData]
            );
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
        Log::error('ProcessWithdrawWebhook: Job falhou após todas as tentativas', [
            'error' => $exception->getMessage(),
            'normalized_data' => $this->normalizedData,
        ]);
    }
}
