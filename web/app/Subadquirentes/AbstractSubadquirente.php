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
        // Se o endpoint já for uma URL completa, usa diretamente
        // Caso contrário, concatena com a base_url
        $url = filter_var($endpoint, FILTER_VALIDATE_URL) 
            ? $endpoint 
            : rtrim($this->getBaseUrl(), '/') . '/' . ltrim($endpoint, '/');
        
        $defaultHeaders = $this->getDefaultHeaders();
        $mergedHeaders = array_merge($defaultHeaders, $headers);

        try {
            Log::info("Subadquirente Request", [
                'subadquirente' => $this->model->name,
                'method' => strtoupper($method),
                'url' => $url,
                'headers' => $mergedHeaders,
                'data' => $data,
            ]);

            $response = Http::timeout(30)
                ->retry(2, 100)
                ->withHeaders($mergedHeaders)
                ->{strtolower($method)}($url, $data);

            $responseData = $response->json() ?? [];
            $statusCode = $response->status();

            Log::info("Subadquirente Response", [
                'subadquirente' => $this->model->name,
                'status' => $statusCode,
                'response' => $responseData,
            ]);

            if (!$response->successful()) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? "Erro na requisição: {$statusCode}";
                throw new \Exception($errorMessage, $statusCode);
            }

            return $responseData;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Erro de conexão com subadquirente", [
                'subadquirente' => $this->model->name,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Erro de conexão com a subadquirente: " . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            Log::error("Erro na requisição para subadquirente", [
                'subadquirente' => $this->model->name,
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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

