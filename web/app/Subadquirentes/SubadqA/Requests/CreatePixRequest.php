<?php

namespace App\Subadquirentes\SubadqA\Requests;

use App\Subadquirentes\SubadqA\BaseRequest;
use Illuminate\Support\Facades\Log;

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
     */
    public function build(array $data): array
    {
        return [
            'amount' => $data['amount'],
            'payer_name' => $data['payer_name'] ?? null,
            'payer_cpf' => $data['payer_cpf'] ?? null,
            'description' => $data['description'] ?? 'Cobrança PIX',
        ];
    }
}

