<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table = 'facturas';

    protected $fillable = [
        'holded_id',
        'contact_id',
        'contact_name',
        'contact',
        'date',
        'total',
        'status',
        'raw_data',
        'google_drive_file_id',
        'proyecto_id',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'date' => 'integer',
        'total' => 'decimal:2',
    ];
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class);
    }
}
