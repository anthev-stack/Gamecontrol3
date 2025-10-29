<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

/**
 * @property int $id
 * @property string $uuid
 * @property string $token
 * @property int $server_id
 * @property int $inviter_id
 * @property string $invitee_email
 * @property int|null $invitee_user_id
 * @property float $share_percentage
 * @property string $status
 * @property string|null $message
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $accepted_at
 */
class BillingInvitation extends Model
{
    public const RESOURCE_NAME = 'billing_invitation';

    protected $table = 'billing_invitations';

    protected $fillable = [
        'uuid',
        'token',
        'server_id',
        'inviter_id',
        'invitee_email',
        'invitee_user_id',
        'share_percentage',
        'status',
        'message',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'share_percentage' => 'decimal:2',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public static array $validationRules = [
        'server_id' => 'required|exists:servers,id',
        'inviter_id' => 'required|exists:users,id',
        'invitee_email' => 'required|email',
        'share_percentage' => 'required|numeric|min:0|max:100',
        'status' => 'required|in:pending,accepted,declined,expired,cancelled',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }
            if (empty($model->token)) {
                $model->token = Str::random(64);
            }
            if (empty($model->expires_at)) {
                $model->expires_at = now()->addDays(7);
            }
        });
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    public function accept(int $userId): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => 'accepted',
            'invitee_user_id' => $userId,
            'accepted_at' => now(),
        ]);

        return true;
    }

    public function decline(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update(['status' => 'declined']);
        return true;
    }
}

