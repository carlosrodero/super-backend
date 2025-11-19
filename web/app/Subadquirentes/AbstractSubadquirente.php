<?php

namespace App\Subadquirentes;

use App\Models\Subadquirente as SubadquirenteModel;
use App\Subadquirentes\Interfaces\SubadquirenteInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractSubadquirente implements SubadquirenteInterface
{
    protected SubadquirenteModel $model;
    protected array $config;

    public function __construct(SubadquirenteModel $model)
    {
        $this->model = $model;
        $this->config = $model->config ?? [];
    }

    /**
     * Retorna a URL base da subadquirente
     */
    protected function getBaseUrl(): string
    {
        return $this->model->base_url;
    }

    /**
     * Faz uma requisição HTTP GET
     */
    protected function get(string $endpoint, array $headers = []): array
    {
        return $this->makeRequest('get', $endpoint, [], $headers);
    }

    /**
     * Faz uma requisição HTTP POST
     */
    protected function post(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->makeRequest('post', $endpoint, $data, $headers);
    }

    /**
     * Faz uma requisição HTTP
     */
    protected function makeRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $url = $this->getBaseUrl() . $endpoint;
        
        $defaultHeaders = $this->getDefaultHeaders();
        $mergedHeaders = array_merge($defaultHeaders, $headers);

        try {
            Log::info("Subadquirente Request", [
                'method' => $method,
                'url' => $url,
                'headers' => $mergedHeaders,
                'data' => $data,
            ]);

            $response = Http::withHeaders($mergedHeaders)
                ->{strtolower($method)}($url, $data);

            $responseData = $response->json() ?? [];
            $statusCode = $response->status();

            Log::info("Subadquirente Response", [
                'status' => $statusCode,
                'response' => $responseData,
            ]);

            if (!$response->successful()) {
                throw new \Exception("Erro na requisição: {$statusCode}", $statusCode);
            }

            return $responseData;
        } catch (\Exception $e) {
            Log::error("Erro na requisição para subadquirente", [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Retorna headers padrão da subadquirente
     */
    protected function getDefaultHeaders(): array
    {
        return $this->config['headers'] ?? [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }
}

