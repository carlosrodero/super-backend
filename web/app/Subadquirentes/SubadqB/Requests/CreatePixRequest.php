<?php

namespace App\Subadquirentes\SubadqB\Requests;

use App\Subadquirentes\SubadqB\BaseRequest;

class CreatePixRequest extends BaseRequest
{
    /**
     * Retorna o resource/endpoint específico
     */
    protected function getResource(): string
    {
        return '/pix/create';
    }

    /**
     * Retorna o nome do mock response
     */
    protected function getMockResponseName(): ?string
    {
        return 'SUCESSO_PIX';
    }

    /**
     * Monta o payload da requisição para criar PIX
     * Formato específico da SubadqB
     */
    public function build(array $data): array
    {
        return [
            'seller_id' => $data['seller_id'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'amount' => $data['amount'],
            'payer' => [
                'name' => $data['payer_name'] ?? null,
                'cpf_cnpj' => $data['payer_cpf'] ?? null,
            ],
            'expires_in' => $data['expires_in'] ?? 3600
        ];
    }
}

