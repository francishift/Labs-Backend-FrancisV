<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseFactura extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'provider_name',
        'date',
        'total',
        'net_amount',
        'tax_amount',
        'irpf_amount',
        'status',
        'notes',
        'google_drive_file_id',
        'raw_data',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'total' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'irpf_amount' => 'decimal:2',
        'raw_data' => 'array',
    ];
}
