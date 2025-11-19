<?php

namespace App\Subadquirentes\SubadqA\Webhook;

use App\Jobs\ProcessPixWebhook;
use App\Jobs\ProcessWithdrawWebhook;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SubadqAWebhookHandler
{

    /**
     * Processa o webhook recebido
     */
    public function handle(array $payload, string $type): void
    {
        try {
            if ($type === 'pix') {
                $this->handlePixWebhook($payload);
            } elseif ($type === 'withdraw') {
                $this->handleWithdrawWebhook($payload);
            } else {
                Log::warning("Tipo de webhook desconhecido", [
                    'type' => $type,
                    'payload' => $payload,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Erro ao processar webhook SubadqA", [
                'type' => $type,
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            throw $e;
        }
    }

    /**
     * Processa webhook de PIX da SubadqA
     */
    protected function handlePixWebhook(array $payload): void
    {
        // Normaliza payload da SubadqA para formato padrão
        $normalized = $this->normalizePixPayload($payload);
        
        // Despacha job para processar webhook de forma assíncrona
        ProcessPixWebhook::dispatch($normalized);
    }

    /**
     * Processa webhook de saque da SubadqA
     */
    protected function handleWithdrawWebhook(array $payload): void
    {
        // Normaliza payload da SubadqA para formato padrão
        $normalized = $this->normalizeWithdrawPayload($payload);
        
        // Despacha job para processar webhook de forma assíncrona
        ProcessWithdrawWebhook::dispatch($normalized);
    }

    /**
     * Normaliza payload de PIX da SubadqA para formato padrão
     */
    protected function normalizePixPayload(array $payload): array
    {
        try {
            $externalId = $payload['transaction_id'] ?? $payload['pix_id'] ?? '';
            $pixId = $payload['pix_id'] ?? $externalId;
            
            $paymentDate = null;
            if (isset($payload['payment_date']) && !empty($payload['payment_date'])) {
                try {
                    $paymentDate = Carbon::parse($payload['payment_date']);
                } catch (\Exception $e) {
                    Log::warning('SubadqAWebhookHandler: Erro ao parsear payment_date', [
                        'date' => $payload['payment_date'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return [
                'external_id' => $externalId,
                'pix_id' => $pixId,
                'status' => $this->normalizeStatus($payload['status'] ?? 'PENDING'),
                'amount' => (float) ($payload['amount'] ?? 0),
                'payer_name' => $payload['payer_name'] ?? null,
                'payer_cpf' => $payload['payer_cpf'] ?? null,
                'payment_date' => $paymentDate?->toDateTimeString(),
                'metadata' => $payload['metadata'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('SubadqAWebhookHandler: Erro ao normalizar payload de PIX', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            throw $e;
        }
    }

    /**
     * Normaliza payload de saque da SubadqA para formato padrão
     */
    protected function normalizeWithdrawPayload(array $payload): array
    {
        try {
            $externalId = $payload['transaction_id'] ?? $payload['withdraw_id'] ?? '';
            $withdrawId = $payload['withdraw_id'] ?? $externalId;

            $requestedAt = null;
            if (isset($payload['requested_at']) && !empty($payload['requested_at'])) {
                try {
                    $requestedAt = Carbon::parse($payload['requested_at']);
                } catch (\Exception $e) {
                    Log::warning('SubadqAWebhookHandler: Erro ao parsear requested_at', [
                        'date' => $payload['requested_at'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $completedAt = null;
            if (isset($payload['completed_at']) && !empty($payload['completed_at'])) {
                try {
                    $completedAt = Carbon::parse($payload['completed_at']);
                } catch (\Exception $e) {
                    Log::warning('SubadqAWebhookHandler: Erro ao parsear completed_at', [
                        'date' => $payload['completed_at'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return [
                'external_id' => $externalId,
                'withdraw_id' => $withdrawId,
                'status' => $this->normalizeWithdrawStatus($payload['status'] ?? 'PENDING'),
                'amount' => (float) ($payload['amount'] ?? 0),
                'bank_account' => $payload['bank_account'] ?? null,
                'requested_at' => $requestedAt?->toDateTimeString(),
                'completed_at' => $completedAt?->toDateTimeString(),
                'metadata' => $payload['metadata'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('SubadqAWebhookHandler: Erro ao normalizar payload de saque', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            throw $e;
        }
    }

    /**
     * Normaliza status de PIX
     */
    protected function normalizeStatus(string $status): string
    {
        $statusMap = [
            'CONFIRMED' => 'CONFIRMED',
            'PAID' => 'PAID',
            'PENDING' => 'PENDING',
            'PROCESSING' => 'PROCESSING',
            'CANCELLED' => 'CANCELLED',
            'FAILED' => 'FAILED',
        ];

        return $statusMap[strtoupper($status)] ?? 'PENDING';
    }

    /**
     * Normaliza status de saque
     */
    protected function normalizeWithdrawStatus(string $status): string
    {
        $statusMap = [
            'SUCCESS' => 'SUCCESS',
            'DONE' => 'SUCCESS',
            'PENDING' => 'PENDING',
            'PROCESSING' => 'PROCESSING',
            'FAILED' => 'FAILED',
            'CANCELLED' => 'CANCELLED',
        ];

        return $statusMap[strtoupper($status)] ?? 'PENDING';
    }
}

