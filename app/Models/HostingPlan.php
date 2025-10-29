<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $slug
 * @property int $memory
 * @property int $swap
 * @property int $disk
 * @property int $io
 * @property int $cpu
 * @property string|null $threads
 * @property int $nest_id
 * @property int $egg_id
 * @property int $database_limit
 * @property int $allocation_limit
 * @property int $backup_limit
 * @property float $price
 * @property string $billing_period
 * @property bool $is_active
 * @property bool $is_featured
 * @property int|null $stock_limit
 * @property int $current_stock
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class HostingPlan extends Model
{
    public const RESOURCE_NAME = 'hosting_plan';

    protected $table = 'hosting_plans';

    protected $fillable = [
        'name',
        'description',
        'slug',
        'memory',
        'swap',
        'disk',
        'io',
        'cpu',
        'threads',
        'nest_id',
        'egg_id',
        'database_limit',
        'allocation_limit',
        'backup_limit',
        'price',
        'billing_period',
        'is_active',
        'is_featured',
        'stock_limit',
        'current_stock',
    ];

    protected $casts = [
        'memory' => 'integer',
        'swap' => 'integer',
        'disk' => 'integer',
        'io' => 'integer',
        'cpu' => 'integer',
        'nest_id' => 'integer',
        'egg_id' => 'integer',
        'database_limit' => 'integer',
        'allocation_limit' => 'integer',
        'backup_limit' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'stock_limit' => 'integer',
        'current_stock' => 'integer',
    ];

    public static array $validationRules = [
        'name' => 'required|string|max:191',
        'slug' => 'required|string|max:191|unique:hosting_plans,slug',
        'memory' => 'required|integer|min:0',
        'swap' => 'required|integer|min:-1',
        'disk' => 'required|integer|min:0',
        'io' => 'required|integer|between:10,1000',
        'cpu' => 'required|integer|min:0',
        'nest_id' => 'required|exists:nests,id',
        'egg_id' => 'required|exists:eggs,id',
        'price' => 'required|numeric|min:0',
        'billing_period' => 'required|in:monthly,quarterly,semi_annually,annually',
    ];

    public function nest(): BelongsTo
    {
        return $this->belongsTo(Nest::class);
    }

    public function egg(): BelongsTo
    {
        return $this->belongsTo(Egg::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'plan_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'plan_id');
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->stock_limit === null) {
            return true; // Unlimited stock
        }

        return $this->current_stock > 0;
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2) . ' ' . strtoupper($this->billing_period);
    }
}


