<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $invoice_id
 * @property string $description
 * @property int $quantity
 * @property float $unit_price
 * @property float $total_price
 */
class InvoiceItem extends Model
{
    public const RESOURCE_NAME = 'invoice_item';

    protected $table = 'invoice_items';

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public static array $validationRules = [
        'invoice_id' => 'required|exists:invoices,id',
        'description' => 'required|string',
        'quantity' => 'required|integer|min:1',
        'unit_price' => 'required|numeric|min:0',
        'total_price' => 'required|numeric|min:0',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}


