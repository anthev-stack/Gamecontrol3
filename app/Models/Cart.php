<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

/**
 * @property int $id
 * @property string $uuid
 * @property string|null $session_token
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Cart extends Model
{
    public const RESOURCE_NAME = 'cart';

    protected $table = 'carts';

    protected $fillable = [
        'uuid',
        'session_token',
        'user_id',
    ];

    public static array $validationRules = [
        'user_id' => 'nullable|exists:users,id',
        'session_token' => 'nullable|string|max:191',
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

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function getTotalAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->price_at_time * $item->quantity;
        });
    }

    public function isEmpty(): bool
    {
        return $this->items->count() === 0;
    }
}


