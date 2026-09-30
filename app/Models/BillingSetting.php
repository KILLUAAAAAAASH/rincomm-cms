<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'billing_cycle_months',
        'disconnection_notice_days',
    ];

    protected $casts = [
        'billing_cycle_months' => 'integer',
        'disconnection_notice_days' => 'integer',
    ];
}
