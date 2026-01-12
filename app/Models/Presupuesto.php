<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presupuesto extends Model
{
    protected $table = 'presupuestos';

    protected $fillable = [
        'holded_id',
        'contact_id',
        'contact_name',
        'contact',
        'date',
        'total',
        'status',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'date' => 'integer',
        'total' => 'decimal:2',
    ];
}
