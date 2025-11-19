<?php

namespace App\Subadquirentes\SubadqA;

use App\Subadquirentes\Requests\AbstractBaseRequest;
use App\Models\Subadquirente;

abstract class BaseRequest extends AbstractBaseRequest
{
    protected Subadquirente $subadquirente;

    public function __construct(Subadquirente $subadquirente)
    {
        $this->subadquirente = $subadquirente;
    }

    /**
     * Retorna a URL base da API
     */
    protected function getBaseUrl(): string
    {
        return $this->subadquirente->base_url;
    }

    /**
     * Retorna o endpoint base
     */
    protected function getBaseEndpoint(): string
    {
        return $this->getBaseUrl();
    }

    /**
     * Retorna os headers da requisição
     */
    public function getHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Adiciona header de mock response se especificado
        $mockResponseName = $this->getMockResponseName();
        if ($mockResponseName) {
            $headers['x-mock-response-name'] = $mockResponseName;
        }

        return $headers;
    }

    /**
     * Retorna o nome do mock response (deve ser implementado nas classes filhas)
     */
    protected function getMockResponseName(): ?string
    {
        return null;
    }
}

