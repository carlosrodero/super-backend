<?php

namespace App\Exceptions;

use Exception;

class WebhookProcessingException extends Exception
{
    protected $message = 'Erro ao processar webhook';

    public function __construct(string $message = null, int $code = 500, Exception $previous = null, protected array $context = [])
    {
        parent::__construct($message ?? $this->message, $code, $previous);
    }

    /**
     * Retorna o contexto adicional do erro
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Renderiza a exception como resposta HTTP
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'error_code' => 'WEBHOOK_PROCESSING_ERROR',
                'context' => $this->context,
            ], $this->getCode() ?: 500);
        }

        return parent::render($request);
    }
}

