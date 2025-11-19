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
        return '[SUCESSO_PIX] pix_create';
    }

    /**
     * Monta o payload da requisição para criar PIX
     * Formato específico da SubadqB
     */
    public function build(array $data): array
    {
        return [
            'value' => $data['amount'],
            'payer' => [
                'name' => $data['payer_name'] ?? null,
                'document' => $data['payer_cpf'] ?? null,
            ],
            'description' => $data['description'] ?? 'Cobrança PIX',
        ];
    }
}

