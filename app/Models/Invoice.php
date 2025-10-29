<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Ramsey\Uuid\Uuid;

/**
 * @property int $id
 * @property string $uuid
 * @property string $invoice_number
 * @property int $user_id
 * @property int|null $server_id
 * @property float $amount
 * @property float $tax
 * @property float $total
 * @property string $currency
 * @property \Illuminate\Support\Carbon|null $period_start
 * @property \Illuminate\Support\Carbon|null $period_end
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $paid_at
 */
class Invoice extends Model
{
    public const RESOURCE_NAME = 'invoice';

    protected $table = 'invoices';

    protected $fillable = [
        'uuid',
        'invoice_number',
        'user_id',
        'server_id',
        'amount',
        'tax',
        'total',
        'currency',
        'period_start',
        'period_end',
        'due_date',
        'status',
        'billing_name',
        'billing_email',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_country',
        'billing_postal_code',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public static array $validationRules = [
        'user_id' => 'required|exists:users,id',
        'invoice_number' => 'required|string|unique:invoices,invoice_number',
        'status' => 'required|in:draft,sent,paid,failed,cancelled',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }
            if (empty($model->invoice_number)) {
                $model->invoice_number = 'INV-' . strtoupper(uniqid());
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
        return $this->hasMany(InvoiceItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isPaid();
    }
}


