<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MantenimientoPrecio extends Model
{
    use HasFactory;

    protected $table = 'mantenimiento_precios';

    protected $fillable = [
        'mantenimiento_id',
        'importe',
        'tipo_pago',
        'fecha_aplicacion',
    ];

    protected $casts = [
        'fecha_aplicacion' => 'date',
        'importe' => 'decimal:2',
    ];

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class);
    }
}
