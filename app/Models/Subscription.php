<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'service_plan_id',
        'start_date',
        'end_date',
        'lock_in_months',
        'discount_amount',
        'is_custom_plan',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'lock_in_months' => 'integer',
        'discount_amount' => 'decimal:2',
        'is_custom_plan' => 'boolean',
    ];

    public function hasLockInPeriod(): bool
    {
        return $this->lock_in_months > 0;
    }

    public function lockInStatus(): string
    {
        if (! $this->hasLockInPeriod()) {
            return 'not_set';
        }

        if ($this->start_date === null || $this->end_date === null) {
            return 'not_started';
        }

        $today = now()->startOfDay();

        if ($today->lt($this->start_date->copy()->startOfDay())) {
            return 'not_started';
        }

        if ($today->lte($this->end_date->copy()->startOfDay())) {
            return 'active';
        }

        return 'completed';
    }

    public function remainingLockInDays(): ?int
    {
        if ($this->lockInStatus() !== 'active') {
            return null;
        }

        return now()
            ->startOfDay()
            ->diffInDays(
                $this->end_date->copy()->startOfDay(),
                false
            );
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function servicePlan(): BelongsTo
    {
        return $this->belongsTo(ServicePlan::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function planChangeRequests(): HasMany
    {
        return $this->hasMany(PlanChangeRequest::class);
    }

    public function relocationRequests(): HasMany
    {
        return $this->hasMany(RelocationRequest::class);
    }
}
