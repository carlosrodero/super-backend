<?php

namespace App\Subadquirentes\SubadqB\Webhook;

use App\Jobs\ProcessPixWebhook;
use App\Jobs\ProcessWithdrawWebhook;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SubadqBWebhookHandler
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
            Log::error("Erro ao processar webhook SubadqB", [
                'type' => $type,
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            throw $e;
        }
    }

    /**
     * Processa webhook de PIX da SubadqB
     */
    protected function handlePixWebhook(array $payload): void
    {
        // Normaliza payload da SubadqB para formato padrão
        $normalized = $this->normalizePixPayload($payload);
        
        // Despacha job para processar webhook de forma assíncrona
        ProcessPixWebhook::dispatch($normalized);
    }

    /**
     * Processa webhook de saque da SubadqB
     */
    protected function handleWithdrawWebhook(array $payload): void
    {
        // Normaliza payload da SubadqB para formato padrão
        $normalized = $this->normalizeWithdrawPayload($payload);
        
        // Despacha job para processar webhook de forma assíncrona
        ProcessWithdrawWebhook::dispatch($normalized);
    }

    /**
     * Normaliza payload de PIX da SubadqB para formato padrão
     */
    protected function normalizePixPayload(array $payload): array
    {
        try {
            $data = $payload['data'] ?? $payload;

            if (!is_array($data)) {
                Log::warning('SubadqBWebhookHandler: Campo "data" não é um array', ['payload' => $payload]);
                $data = $payload;
            }

            $externalId = $data['id'] ?? '';
            $pixId = $externalId;

            $payerName = null;
            $payerCpf = null;
            if (isset($data['payer']) && is_array($data['payer'])) {
                $payerName = $data['payer']['name'] ?? null;
                $payerCpf = $data['payer']['document'] ?? null;
            }

            $paymentDate = null;
            if (isset($data['confirmed_at']) && !empty($data['confirmed_at'])) {
                try {
                    $paymentDate = Carbon::parse($data['confirmed_at']);
                } catch (\Exception $e) {
                    Log::warning('SubadqBWebhookHandler: Erro ao parsear confirmed_at', [
                        'date' => $data['confirmed_at'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return [
                'external_id' => $externalId,
                'pix_id' => $pixId,
                'status' => $this->normalizeStatus($data['status'] ?? 'PENDING'),
                'amount' => (float) ($data['value'] ?? $data['amount'] ?? 0),
                'payer_name' => $payerName,
                'payer_cpf' => $payerCpf,
                'payment_date' => $paymentDate?->toDateTimeString(),
                'metadata' => array_merge(
                    $payload['metadata'] ?? [],
                    ['signature' => $payload['signature'] ?? null]
                ),
            ];
        } catch (\Exception $e) {
            Log::error('SubadqBWebhookHandler: Erro ao normalizar payload de PIX', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            throw $e;
        }
    }

    /**
     * Normaliza payload de saque da SubadqB para formato padrão
     */
    protected function normalizeWithdrawPayload(array $payload): array
    {
        try {
            $data = $payload['data'] ?? $payload;

            if (!is_array($data)) {
                Log::warning('SubadqBWebhookHandler: Campo "data" não é um array', ['payload' => $payload]);
                $data = $payload;
            }

            $externalId = $data['id'] ?? '';
            $withdrawId = $externalId;

            $requestedAt = null;
            if (isset($data['requested_at']) && !empty($data['requested_at'])) {
                try {
                    $requestedAt = Carbon::parse($data['requested_at']);
                } catch (\Exception $e) {
                    Log::warning('SubadqBWebhookHandler: Erro ao parsear requested_at', [
                        'date' => $data['requested_at'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $completedAt = null;
            if (isset($data['processed_at']) && !empty($data['processed_at'])) {
                try {
                    $completedAt = Carbon::parse($data['processed_at']);
                } catch (\Exception $e) {
                    Log::warning('SubadqBWebhookHandler: Erro ao parsear processed_at', [
                        'date' => $data['processed_at'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return [
                'external_id' => $externalId,
                'withdraw_id' => $withdrawId,
                'status' => $this->normalizeWithdrawStatus($data['status'] ?? 'PENDING'),
                'amount' => (float) ($data['amount'] ?? 0),
                'bank_account' => $data['bank_account'] ?? null,
                'requested_at' => $requestedAt?->toDateTimeString(),
                'completed_at' => $completedAt?->toDateTimeString(),
                'metadata' => array_merge(
                    $payload['metadata'] ?? [],
                    ['signature' => $payload['signature'] ?? null]
                ),
            ];
        } catch (\Exception $e) {
            Log::error('SubadqBWebhookHandler: Erro ao normalizar payload de saque', [
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

