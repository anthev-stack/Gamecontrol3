<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $plan_id
 * @property int $quantity
 * @property float $price
 * @property string|null $plan_snapshot
 */
class OrderItem extends Model
{
    public const RESOURCE_NAME = 'order_item';

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'plan_id',
        'quantity',
        'price',
        'plan_snapshot',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(HostingPlan::class, 'plan_id');
    }

    public function getSubtotalAttribute(): float
    {
        return $this->price * $this->quantity;
    }
}


