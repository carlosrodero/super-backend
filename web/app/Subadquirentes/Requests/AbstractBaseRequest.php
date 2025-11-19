<?php

namespace App\Subadquirentes\Requests;

abstract class AbstractBaseRequest
{
    /**
     * Retorna a URL base da API
     */
    abstract protected function getBaseUrl(): string;

    /**
     * Retorna os headers da requisição
     */
    abstract public function getHeaders(): array;

    /**
     * Retorna o hash de autenticação (se necessário)
     */
    protected function getAuthHash(): ?string
    {
        return null;
    }

    /**
     * Retorna o endpoint completo
     */
    public function getEndpoint(string $resource = null): string
    {
        $baseEndpoint = $this->getBaseEndpoint();
        $version = $this->getVersion();
        $resource = $resource ?? $this->getResource();

        $endpoint = $baseEndpoint;
        if ($version) {
            $endpoint .= '/' . $version;
        }
        if ($resource) {
            $endpoint .= $resource;
        }

        return $endpoint;
    }

    /**
     * Retorna o endpoint base (sem versão e resource)
     */
    protected function getBaseEndpoint(): string
    {
        return '';
    }

    /**
     * Retorna a versão da API (se houver)
     */
    protected function getVersion(): ?string
    {
        return null;
    }

    /**
     * Retorna o resource/endpoint específico
     */
    abstract protected function getResource(): string;

    /**
     * Monta o payload da requisição
     */
    abstract public function build(array $data): array;
}

