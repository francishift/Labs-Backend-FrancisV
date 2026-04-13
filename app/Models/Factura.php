<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table = 'facturas';

    protected $appends = ['contact_name'];

    protected $fillable = [
        'number',
        'client_id',
        'date',
        'due_date',
        'total',
        'subtotal',
        'tax_amount',
        'irpf_amount',
        'status',
        'raw_data',
        'google_drive_file_id',
        'proyecto_id',
        'notes',
        'description',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'date' => 'integer',
        'due_date' => 'integer',
        'total' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'irpf_amount' => 'decimal:2',
        'status' => \App\Enums\FacturaStatus::class,
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function lineas()
    {
        return $this->hasMany(FacturaLinea::class);
    }

    public function getContactNameAttribute()
    {
        return $this->cliente->name 
            ?? $this->raw_data['contactName'] 
            ?? $this->raw_data['contact'] 
            ?? 'Cliente Desconocido';
    }
}
