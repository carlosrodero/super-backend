<?php

namespace App\Exceptions;

use Exception;

class SubadquirenteNotFoundException extends Exception
{
    protected $message = 'Subadquirente não encontrada ou inativa';

    public function __construct(string $message = null, int $code = 404, Exception $previous = null)
    {
        parent::__construct($message ?? $this->message, $code, $previous);
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
                'error_code' => 'SUBADQUIRENTE_NOT_FOUND',
            ], $this->getCode() ?: 404);
        }

        return parent::render($request);
    }
}

