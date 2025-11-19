<?php

namespace App\Subadquirentes\SubadqA\Webhook;

use App\DTOs\PixWebhookDTO;
use App\DTOs\WithdrawWebhookDTO;
use App\Services\PixService;
use App\Services\WithdrawService;
use Illuminate\Support\Facades\Log;

class SubadqAWebhookHandler
{
    protected PixService $pixService;
    protected WithdrawService $withdrawService;

    public function __construct()
    {
        $this->pixService = app(PixService::class);
        $this->withdrawService = app(WithdrawService::class);
    }

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
        // Transforma payload da SubadqA para DTO normalizado
        $dto = PixWebhookDTO::fromSubadqA($payload);
        
        // Processa o webhook através do service
        $this->pixService->processWebhook($dto);
    }

    /**
     * Processa webhook de saque da SubadqA
     */
    protected function handleWithdrawWebhook(array $payload): void
    {
        // Transforma payload da SubadqA para DTO normalizado
        $dto = WithdrawWebhookDTO::fromSubadqA($payload);
        
        // Processa o webhook através do service
        $this->withdrawService->processWebhook($dto);
    }
}

