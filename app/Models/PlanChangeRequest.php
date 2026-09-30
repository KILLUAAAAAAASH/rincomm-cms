<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanChangeRequest extends Model
{
    protected $fillable = [
        'customer_id',
        'subscription_id',
        'current_service_plan_id',
        'requested_service_plan_id',
        'requested_by',
        'reviewed_by',
        'request_type',
        'status',
        'reason',
        'review_notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function currentPlan(): BelongsTo
    {
        return $this->belongsTo(
            ServicePlan::class,
            'current_service_plan_id'
        );
    }

    public function requestedPlan(): BelongsTo
    {
        return $this->belongsTo(
            ServicePlan::class,
            'requested_service_plan_id'
        );
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'requested_by'
        );
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by'
        );
    }
}
