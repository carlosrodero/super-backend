<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateWithdrawRequest;
use App\Services\WithdrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WithdrawController extends Controller
{
    protected WithdrawService $withdrawService;

    public function __construct(WithdrawService $withdrawService)
    {
        $this->withdrawService = $withdrawService;
    }

    /**
     * Cria um novo saque
     *
     * @param CreateWithdrawRequest $request
     * @return JsonResponse
     */
    public function store(CreateWithdrawRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'Usuário não autenticado',
                ], 401);
            }

            // Cria o saque através do service
            $withdraw = $this->withdrawService->createWithdraw($request->validated(), $user);

            Log::info('Saque criado via API', [
                'withdraw_id' => $withdraw->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Saque criado com sucesso',
                'data' => [
                    'id' => $withdraw->id,
                    'external_id' => $withdraw->external_id,
                    'amount' => $withdraw->amount,
                    'status' => $withdraw->status,
                    'requested_at' => $withdraw->requested_at,
                    'created_at' => $withdraw->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erro ao criar saque via API', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Erro ao criar saque',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lista todos os saques do usuário autenticado
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

            $withdraws = $user->withdraws()->orderBy('created_at', 'desc')->get();

            return response()->json([
                'data' => $withdraws->map(function ($withdraw) {
                    return [
                        'id' => $withdraw->id,
                        'external_id' => $withdraw->external_id,
                        'amount' => $withdraw->amount,
                        'status' => $withdraw->status,
                        'bank_account' => $withdraw->bank_account,
                        'requested_at' => $withdraw->requested_at,
                        'completed_at' => $withdraw->completed_at,
                        'created_at' => $withdraw->created_at,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar saques via API', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Erro ao listar saques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exibe um saque específico
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

            $withdraw = $user->withdraws()->findOrFail($id);

            return response()->json([
                'data' => [
                    'id' => $withdraw->id,
                    'external_id' => $withdraw->external_id,
                    'amount' => $withdraw->amount,
                    'status' => $withdraw->status,
                    'bank_account' => $withdraw->bank_account,
                    'requested_at' => $withdraw->requested_at,
                    'completed_at' => $withdraw->completed_at,
                    'metadata' => $withdraw->metadata,
                    'created_at' => $withdraw->created_at,
                    'updated_at' => $withdraw->updated_at,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Saque não encontrado',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar saque via API', [
                'error' => $e->getMessage(),
                'withdraw_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Erro ao buscar saque',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
