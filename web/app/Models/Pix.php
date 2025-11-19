<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pix extends Model
{
    use HasFactory;

    /**
     * Status possíveis para PIX
     */
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_PAID = 'PAID';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_FAILED = 'FAILED';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'subadquirente_id',
        'external_id',
        'amount',
        'status',
        'payer_name',
        'payer_cpf',
        'payment_date',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relacionamento: Um PIX pertence a um usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento: Um PIX pertence a uma subadquirente
     */
    public function subadquirente(): BelongsTo
    {
        return $this->belongsTo(Subadquirente::class);
    }

    /**
     * Verifica se o PIX está pendente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Verifica se o PIX foi confirmado/pago
     */
    public function isPaid(): bool
    {
        return in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_PAID]);
    }

    /**
     * Verifica se o PIX falhou ou foi cancelado
     */
    public function isFailed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }
}
