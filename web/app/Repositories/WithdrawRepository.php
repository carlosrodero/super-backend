<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class WithdrawRepository
{
    /**
     * Cria um novo saque
     *
     * @param array $data
     * @return Withdraw
     */
    public function create(array $data): Withdraw
    {
        try {
            return Withdraw::create($data);
        } catch (\Exception $e) {
            Log::error('WithdrawRepository: Erro ao criar saque', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza um saque
     *
     * @param Withdraw $withdraw
     * @param array $data
     * @return bool
     */
    public function update(Withdraw $withdraw, array $data): bool
    {
        try {
            return $withdraw->update($data);
        } catch (\Exception $e) {
            Log::error('WithdrawRepository: Erro ao atualizar saque', [
                'withdraw_id' => $withdraw->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Busca saque pelo ID externo (external_id)
     *
     * @param string $externalId
     * @return Withdraw|null
     */
    public function findByExternalId(string $externalId): ?Withdraw
    {
        return Withdraw::where('external_id', $externalId)->first();
    }

    /**
     * Busca saque pelo ID externo ou pelo withdraw_id
     *
     * @param string $identifier
     * @return Withdraw|null
     */
    public function findByExternalIdOrWithdrawId(string $identifier): ?Withdraw
    {
        return Withdraw::where('external_id', $identifier)
            ->orWhere('withdraw_id', $identifier)
            ->first();
    }

    /**
     * Busca todos os saques de um usuário
     *
     * @param User $user
     * @return Collection
     */
    public function findByUser(User $user): Collection
    {
        return Withdraw::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Busca saque por ID
     *
     * @param int $id
     * @return Withdraw|null
     */
    public function findById(int $id): ?Withdraw
    {
        return Withdraw::find($id);
    }

    /**
     * Busca saques por status
     *
     * @param string $status
     * @return Collection
     */
    public function findByStatus(string $status): Collection
    {
        return Withdraw::where('status', $status)->get();
    }
}

