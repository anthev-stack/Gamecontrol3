<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

/**
 * @property int $id
 * @property string $uuid
 * @property string $transaction_id
 * @property int $user_id
 * @property int|null $order_id
 * @property int|null $invoice_id
 * @property float $amount
 * @property string $currency
 * @property string $payment_method
 * @property string|null $gateway
 * @property string|null $gateway_transaction_id
 * @property string|null $gateway_response
 * @property string $status
 */
class Payment extends Model
{
    public const RESOURCE_NAME = 'payment';

    protected $table = 'payments';

    protected $fillable = [
        'uuid',
        'transaction_id',
        'user_id',
        'order_id',
        'invoice_id',
        'amount',
        'currency',
        'payment_method',
        'gateway',
        'gateway_transaction_id',
        'gateway_response',
        'status',
        'billing_name',
        'billing_email',
        'notes',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    public static array $validationRules = [
        'user_id' => 'required|exists:users,id',
        'transaction_id' => 'required|string|unique:payments,transaction_id',
        'amount' => 'required|numeric|min:0',
        'payment_method' => 'required|in:stripe,paypal,bank_transfer,cash,other',
        'status' => 'required|in:pending,processing,completed,failed,refunded,cancelled',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }
            if (empty($model->transaction_id)) {
                $model->transaction_id = 'TXN-' . strtoupper(uniqid());
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }
}


