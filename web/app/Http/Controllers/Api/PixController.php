<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePixRequest;
use App\Models\Pix;
use App\Services\PixService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PixController extends Controller
{
    protected PixService $pixService;

    public function __construct(PixService $pixService)
    {
        $this->pixService = $pixService;
    }

    /**
     * Cria uma nova cobrança PIX
     *
     * @param CreatePixRequest $request
     * @return JsonResponse
     */
    public function store(CreatePixRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'Usuário não autenticado',
                ], 401);
            }

            // Cria o PIX através do service
            $pix = $this->pixService->createPix($request->validated(), $user);

            Log::info('PIX criado via API', [
                'pix_id' => $pix->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'PIX criado com sucesso',
                'data' => [
                    'id' => $pix->id,
                    'external_id' => $pix->external_id,
                    'amount' => $pix->amount,
                    'status' => $pix->status,
                    'created_at' => $pix->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erro ao criar PIX via API', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Erro ao criar PIX',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lista todos os PIX do usuário autenticado
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'Usuário não autenticado',
                ], 401);
            }

            $pixList = $user->pix()->orderBy('created_at', 'desc')->get();

            return response()->json([
                'data' => $pixList->map(function ($pix) {
                    return [
                        'id' => $pix->id,
                        'external_id' => $pix->external_id,
                        'amount' => $pix->amount,
                        'status' => $pix->status,
                        'payer_name' => $pix->payer_name,
                        'payer_cpf' => $pix->payer_cpf,
                        'payment_date' => $pix->payment_date,
                        'created_at' => $pix->created_at,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar PIX via API', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Erro ao listar PIX',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exibe um PIX específico
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'Usuário não autenticado',
                ], 401);
            }

            $pix = $user->pix()->findOrFail($id);

            return response()->json([
                'data' => [
                    'id' => $pix->id,
                    'external_id' => $pix->external_id,
                    'amount' => $pix->amount,
                    'status' => $pix->status,
                    'payer_name' => $pix->payer_name,
                    'payer_cpf' => $pix->payer_cpf,
                    'payment_date' => $pix->payment_date,
                    'metadata' => $pix->metadata,
                    'created_at' => $pix->created_at,
                    'updated_at' => $pix->updated_at,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'PIX não encontrado',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar PIX via API', [
                'error' => $e->getMessage(),
                'pix_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Erro ao buscar PIX',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
