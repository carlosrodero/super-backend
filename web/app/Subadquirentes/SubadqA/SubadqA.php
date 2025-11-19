<?php

namespace App\Subadquirentes\SubadqA;

use App\Models\Subadquirente as SubadquirenteModel;
use App\Subadquirentes\AbstractSubadquirente;
use App\Subadquirentes\SubadqA\Requests\CreatePixRequest;
use App\Subadquirentes\SubadqA\Requests\CreateWithdrawRequest;
use App\Subadquirentes\SubadqA\Webhook\SubadqAWebhookHandler;

class SubadqA extends AbstractSubadquirente
{
    /**
     * Cria uma cobrança PIX na SubadqA
     */
    public function createPix(array $data): array
    {
        $request = new CreatePixRequest($this->model);
        $payload = $request->build($data);
        $endpoint = $request->getEndpoint();
        $headers = $request->getHeaders();

        return $this->post($endpoint, $payload, $headers);
    }

    /**
     * Cria um saque na SubadqA
     */
    public function createWithdraw(array $data): array
    {
        $request = new CreateWithdrawRequest($this->model);
        $payload = $request->build($data);
        $endpoint = $request->getEndpoint();
        $headers = $request->getHeaders();

        return $this->post($endpoint, $payload, $headers);
    }

    /**
     * Processa um webhook recebido da SubadqA
     */
    public function processWebhook(array $payload, string $type): void
    {
        $handler = new SubadqAWebhookHandler();
        $handler->handle($payload, $type);
    }
}

