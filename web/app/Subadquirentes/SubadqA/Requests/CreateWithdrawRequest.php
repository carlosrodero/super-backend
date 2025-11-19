<?php

namespace App\Subadquirentes\SubadqA\Requests;

use App\Subadquirentes\SubadqA\BaseRequest;

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
        return 'SUCESSO_WD';
    }

    /**
     * Monta o payload da requisição para criar saque
     */
    public function build(array $data): array
    {
        return [
            'amount' => $data['amount'],
            'bank_account' => $data['bank_account'],
        ];
    }
}

