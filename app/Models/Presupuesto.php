<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presupuesto extends Model
{
    protected $table = 'presupuestos';

    protected $appends = ['contact_name'];

    protected $fillable = [
        'client_id',
        'date',
        'due_date',
        'total',
        'status',
        'raw_data',
        'google_drive_file_id',
        'number',
        'subtotal',
        'tax_amount',
        'irpf_amount',
        'notes',
        'description',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'date' => 'integer',
        'total' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'irpf_amount' => 'decimal:2',
        'status' => \App\Enums\PresupuestoStatus::class,
    ];

    public function proyectos()
    {
        return $this->hasMany(Proyecto::class, 'presupuesto_id');
    }

    public function lineas()
    {
        return $this->hasMany(PresupuestoLinea::class, 'presupuesto_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function getContactNameAttribute()
    {
        return $this->cliente->name 
            ?? $this->raw_data['contactName'] 
            ?? $this->raw_data['contact'] 
            ?? 'Cliente Desconocido';
    }
}
