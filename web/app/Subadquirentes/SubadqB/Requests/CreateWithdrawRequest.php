<?php

namespace App\Subadquirentes\SubadqB\Requests;

use App\Subadquirentes\SubadqB\BaseRequest;

class CreateWithdrawRequest extends BaseRequest
{
    /**
     * Retorna o resource/endpoint específico
     */
    protected function getResource(): string
    {
        return '/withdraw';
    }

    /**
     * Retorna o nome do mock response
     */
    protected function getMockResponseName(): ?string
    {
        return '[SUCESSO_WD] withdraw';
    }

    /**
     * Monta o payload da requisição para criar saque
     * Formato específico da SubadqB
     */
    public function build(array $data): array
    {
        return [
            'amount' => $data['amount'],
            'bank_account' => $data['bank_account'],
        ];
    }
}

