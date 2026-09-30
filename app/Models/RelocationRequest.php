<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelocationRequest extends Model
{
    protected $fillable = [
        'customer_id',
        'subscription_id',
        'requested_service_area_id',
        'requested_by',
        'reviewed_by',
        'current_installation_address',
        'requested_installation_address',
        'requested_province',
        'requested_city_municipality',
        'requested_barangay',
        'requested_postal_code',
        'status',
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

    public function requestedServiceArea(): BelongsTo
    {
        return $this->belongsTo(
            ServiceArea::class,
            'requested_service_area_id'
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
