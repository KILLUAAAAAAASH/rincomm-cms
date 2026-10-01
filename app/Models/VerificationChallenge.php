<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationChallenge extends Model
{
    use HasFactory;

    public const PURPOSE_REGISTRATION = 'registration';

    public const PURPOSE_EMPLOYEE_ACTIVATION = 'employee_activation';

    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_SMS = 'sms';

    protected $fillable = [
        'user_id',
        'public_id',
        'purpose',
        'channel',
        'destination',
        'code_hash',
        'attempts',
        'resend_count',
        'last_sent_at',
        'expires_at',
        'verified_at',
        'consumed_at',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'resend_count' => 'integer',
            'last_sent_at' => 'datetime',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isPending(): bool
    {
        return ! $this->isExpired()
            && ! $this->isVerified()
            && ! $this->isConsumed();
    }
}
