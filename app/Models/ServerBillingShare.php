<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $server_id
 * @property int $user_id
 * @property float $share_percentage
 * @property string $status
 * @property bool $has_server_access
 */
class ServerBillingShare extends Model
{
    public const RESOURCE_NAME = 'server_billing_share';

    protected $table = 'server_billing_shares';

    protected $fillable = [
        'server_id',
        'user_id',
        'share_percentage',
        'status',
        'has_server_access',
    ];

    protected $casts = [
        'share_percentage' => 'decimal:2',
        'has_server_access' => 'boolean',
    ];

    public static array $validationRules = [
        'server_id' => 'required|exists:servers,id',
        'user_id' => 'required|exists:users,id',
        'share_percentage' => 'required|numeric|min:0|max:100',
        'status' => 'required|in:active,pending,cancelled',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calculateAmount(float $totalAmount): float
    {
        return ($totalAmount * $this->share_percentage) / 100;
    }
}

