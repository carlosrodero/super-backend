<?php

namespace App\Subadquirentes\SubadqB;

use App\Models\Subadquirente as SubadquirenteModel;
use App\Subadquirentes\AbstractSubadquirente;
use App\Subadquirentes\SubadqB\Requests\CreatePixRequest;
use App\Subadquirentes\SubadqB\Requests\CreateWithdrawRequest;
use App\Subadquirentes\SubadqB\Webhook\SubadqBWebhookHandler;

class SubadqB extends AbstractSubadquirente
{
    /**
     * Cria uma cobrança PIX na SubadqB
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
     * Cria um saque na SubadqB
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
     * Processa um webhook recebido da SubadqB
     */
    public function processWebhook(array $payload, string $type): void
    {
        $handler = new SubadqBWebhookHandler();
        $handler->handle($payload, $type);
    }
}

