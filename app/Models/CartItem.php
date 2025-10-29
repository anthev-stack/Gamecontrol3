<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $cart_id
 * @property int $plan_id
 * @property int $quantity
 * @property float $price_at_time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CartItem extends Model
{
    public const RESOURCE_NAME = 'cart_item';

    protected $table = 'cart_items';

    protected $fillable = [
        'cart_id',
        'plan_id',
        'quantity',
        'price_at_time',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_at_time' => 'decimal:2',
    ];

    public static array $validationRules = [
        'cart_id' => 'required|exists:carts,id',
        'plan_id' => 'required|exists:hosting_plans,id',
        'quantity' => 'required|integer|min:1',
        'price_at_time' => 'required|numeric|min:0',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(HostingPlan::class, 'plan_id');
    }

    public function getSubtotalAttribute(): float
    {
        return $this->price_at_time * $this->quantity;
    }
}


