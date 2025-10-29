<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Ramsey\Uuid\Uuid;

/**
 * @property int $id
 * @property string $uuid
 * @property string $order_number
 * @property int $user_id
 * @property int|null $server_id
 * @property float $subtotal
 * @property float $tax
 * @property float $total
 * @property string $currency
 * @property string $status
 * @property string|null $billing_name
 * @property string|null $billing_email
 * @property string|null $billing_address
 * @property string|null $billing_city
 * @property string|null $billing_state
 * @property string|null $billing_country
 * @property string|null $billing_postal_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 */
class Order extends Model
{
    public const RESOURCE_NAME = 'order';

    protected $table = 'orders';

    protected $fillable = [
        'uuid',
        'order_number',
        'user_id',
        'server_id',
        'subtotal',
        'tax',
        'total',
        'currency',
        'status',
        'billing_name',
        'billing_email',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_country',
        'billing_postal_code',
        'completed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public static array $validationRules = [
        'user_id' => 'required|exists:users,id',
        'order_number' => 'required|string|unique:orders,order_number',
        'status' => 'required|in:pending,processing,completed,failed,cancelled',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }
            if (empty($model->order_number)) {
                $model->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}

