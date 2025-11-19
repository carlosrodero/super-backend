<?php

namespace App\Exceptions;

use Exception;

class PixCreationException extends Exception
{
    protected $message = 'Erro ao criar cobrança PIX';

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
                'error_code' => 'PIX_CREATION_ERROR',
                'context' => $this->context,
            ], $this->getCode() ?: 500);
        }

        return parent::render($request);
    }
}

