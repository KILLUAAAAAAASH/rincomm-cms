<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfileUpdateRequest extends Model
{
    protected $fillable = [
        'customer_id', 'requested_by', 'reviewed_by', 'status',
        'current_values', 'requested_values', 'reason',
        'review_notes', 'reviewed_at', 'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'current_values' => 'array',
            'requested_values' => 'array',
            'reviewed_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
