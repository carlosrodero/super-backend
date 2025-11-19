<?php

namespace App\Services;

use App\Models\Subadquirente as SubadquirenteModel;
use App\Models\User;
use App\Subadquirentes\Interfaces\SubadquirenteInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubadquirenteServiceFactory
{
    /**
     * Retorna a instância da subadquirente baseada no modelo
     * Usa instanciação dinâmica baseada no namespace
     *
     * @param SubadquirenteModel $model
     * @return SubadquirenteInterface
     * @throws \Exception
     */
    public static function make(SubadquirenteModel $model): SubadquirenteInterface
    {
        $namespace = self::getNamespace($model->name);
        
        if (!class_exists($namespace)) {
            throw new \Exception("Subadquirente '{$model->name}' não encontrada ou não implementada. Classe esperada: {$namespace}");
        }

        $instance = new $namespace($model);

        if (!$instance instanceof SubadquirenteInterface) {
            throw new \Exception("A classe '{$namespace}' não implementa SubadquirenteInterface");
        }

        return $instance;
    }

    /**
     * Retorna a instância da subadquirente pelo nome
     *
     * @param string $name
     * @return SubadquirenteInterface
     * @throws \Exception
     */
    public static function makeByName(string $name): SubadquirenteInterface
    {
        $subadquirente = SubadquirenteModel::where('name', $name)
            ->where('active', true)
            ->first();

        if (!$subadquirente) {
            throw new \Exception("Subadquirente '{$name}' não encontrada ou inativa");
        }

        return self::make($subadquirente);
    }

    /**
     * Retorna a instância da subadquirente do usuário autenticado
     *
     * @param User|null $user Se não fornecido, usa o usuário autenticado
     * @return SubadquirenteInterface
     * @throws \Exception
     */
    public static function makeFromAuthenticatedUser(?User $user = null): SubadquirenteInterface
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            throw new \Exception('Usuário não autenticado');
        }

        if (!$user->subadquirente) {
            throw new \Exception("Usuário '{$user->name}' não possui subadquirente configurada");
        }

        if (!$user->subadquirente->active) {
            throw new \Exception("Subadquirente '{$user->subadquirente->name}' está inativa");
        }

        Log::info('Subadquirente encontrada: ' . $user->subadquirente->name);

        return self::make($user->subadquirente);
    }

    /**
     * Monta o namespace completo da classe da subadquirente
     * 
     * Exemplo: "SubadqA" → "App\Subadquirentes\SubadqA\SubadqA"
     * 
     * @param string $subadquirenteName Nome da subadquirente
     * @return string Namespace completo
     */
    protected static function getNamespace(string $subadquirenteName): string
    {
        $normalizedName = self::normalizeSubadquirenteName($subadquirenteName);
        return sprintf('App\Subadquirentes\%s\%s', $normalizedName, $normalizedName);
    }

    /**
     * Normaliza o nome da subadquirente para o formato do namespace
     * Remove espaços, pontos e outros caracteres especiais
     * 
     * @param string $name Nome original
     * @return string Nome normalizado
     */
    protected static function normalizeSubadquirenteName(string $name): string
    {
        // Remove espaços, pontos e outros caracteres especiais
        $normalized = str_replace([' ', '.', '-', '_'], '', $name);
        
        // Garante que a primeira letra seja maiúscula
        return ucfirst($normalized);
    }
}

