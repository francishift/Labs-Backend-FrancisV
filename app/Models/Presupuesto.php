<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presupuesto extends Model
{
    protected $table = 'presupuestos';

    protected $fillable = [
        'holded_id',
        'client_id',
        'contact_name',
        'contact',
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
}
