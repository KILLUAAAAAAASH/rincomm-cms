<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentAsset extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'ont_onu' => 'ONT / ONU',
        'router' => 'Router',
        'modem' => 'Modem',
        'switch' => 'Switch',
        'olt' => 'OLT',
        'access_point' => 'Access Point',
        'other' => 'Other',
    ];

    // Customer assignments and retrieval states are deliberately deferred to
    // the next FRS features, to avoid falsely claiming a device is deployed.
    public const STATUSES = [
        'in_stock' => 'In Stock',
        'maintenance' => 'Under Maintenance',
        'faulty' => 'Faulty',
        'retired' => 'Retired',
    ];

    protected $fillable = [
        'asset_tag',
        'name',
        'category',
        'brand',
        'model',
        'serial_number',
        'status',
        'notes',
    ];
}
