<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $admin_id
 * @property float $amount
 * @property float $balance_after
 * @property string $type
 * @property string|null $reason
 * @property string|null $description
 * @property int|null $order_id
 * @property array|null $metadata
 */
class CreditTransaction extends Model
{
    public const RESOURCE_NAME = 'credit_transaction';

    protected $table = 'credit_transactions';

    protected $fillable = [
        'uuid',
        'user_id',
        'admin_id',
        'amount',
        'balance_after',
        'type',
        'reason',
        'description',
        'order_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    public static array $validationRules = [
        'user_id' => 'required|exists:users,id',
        'amount' => 'required|numeric',
        'type' => 'required|in:admin_grant,purchase,refund,payment',
        'reason' => 'nullable|in:giveaway,refund,gift,purchase,payment,other',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    public function isDebit(): bool
    {
        return $this->amount < 0;
    }

    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->amount > 0 ? '+' : '';
        return $prefix . '$' . number_format(abs($this->amount), 2);
    }
}

