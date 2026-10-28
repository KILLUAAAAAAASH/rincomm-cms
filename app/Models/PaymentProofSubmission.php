<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProofSubmission extends Model
{
    protected $fillable = [
        'invoice_id', 'customer_id', 'payment_method',
        'transaction_reference', 'amount', 'proof_path', 'status',
        'reviewed_by_user_id', 'reviewed_at', 'review_notes', 'payment_id',
    ];

    protected $casts = ['amount' => 'decimal:2', 'reviewed_at' => 'datetime'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
