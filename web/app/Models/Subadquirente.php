<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subadquirente extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'base_url',
        'config',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'config' => 'array',
        'active' => 'boolean',
    ];

    /**
     * Relacionamento: Uma subadquirente tem muitos usuários
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Relacionamento: Uma subadquirente tem muitos PIX
     */
    public function pix(): HasMany
    {
        return $this->hasMany(Pix::class);
    }

    /**
     * Relacionamento: Uma subadquirente tem muitos saques
     */
    public function withdraws(): HasMany
    {
        return $this->hasMany(Withdraw::class);
    }
}
