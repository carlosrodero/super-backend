<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Loga exceptions não tratadas
            if (!$this->isHttpException($e) && !($e instanceof ValidationException)) {
                Log::error('Exception não tratada', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        });
    }

    /**
     * Renderiza uma exception em uma resposta HTTP.
     *
     * @param Request $request
     * @param Throwable $e
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     * @throws Throwable
     */
    public function render($request, Throwable $e)
    {
        // Exceptions customizadas já têm seu próprio método render()
        if ($e instanceof SubadquirenteNotFoundException ||
            $e instanceof PixCreationException ||
            $e instanceof WithdrawCreationException ||
            $e instanceof WebhookProcessingException) {
            return $e->render($request);
        }

        return parent::render($request, $e);
    }
}
