<?php

namespace App\Repositories;

use App\Models\Pix;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class PixRepository
{
    /**
     * Cria uma nova cobrança PIX
     *
     * @param array $data
     * @return Pix
     */
    public function create(array $data): Pix
    {
        try {
            return Pix::create($data);
        } catch (\Exception $e) {
            Log::error('PixRepository: Erro ao criar PIX', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza uma cobrança PIX
     *
     * @param Pix $pix
     * @param array $data
     * @return bool
     */
    public function update(Pix $pix, array $data): bool
    {
        try {
            return $pix->update($data);
        } catch (\Exception $e) {
            Log::error('PixRepository: Erro ao atualizar PIX', [
                'pix_id' => $pix->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Busca PIX pelo ID externo (external_id)
     *
     * @param string $externalId
     * @return Pix|null
     */
    public function findByExternalId(string $externalId): ?Pix
    {
        return Pix::where('external_id', $externalId)->first();
    }

    /**
     * Busca PIX pelo ID externo ou pelo pix_id
     *
     * @param string $identifier
     * @return Pix|null
     */
    public function findByExternalIdOrPixId(string $identifier): ?Pix
    {
        return Pix::where('external_id', $identifier)
            ->orWhere('pix_id', $identifier)
            ->first();
    }

    /**
     * Busca todos os PIX de um usuário
     *
     * @param User $user
     * @return Collection
     */
    public function findByUser(User $user): Collection
    {
        return Pix::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Busca PIX por ID
     *
     * @param int $id
     * @return Pix|null
     */
    public function findById(int $id): ?Pix
    {
        return Pix::find($id);
    }

    /**
     * Busca PIX por status
     *
     * @param string $status
     * @return Collection
     */
    public function findByStatus(string $status): Collection
    {
        return Pix::where('status', $status)->get();
    }
}

