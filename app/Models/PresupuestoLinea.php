<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresupuestoLinea extends Model
{
    use HasFactory;

    protected $table = 'presupuesto_lineas';

    protected $fillable = [
        'presupuesto_id',
        'concepto',
        'cantidad',
        'precio_unitario',
        'porcentaje_iva',
        'porcentaje_irpf',
        'total_linea',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'porcentaje_iva' => 'decimal:2',
        'porcentaje_irpf' => 'decimal:2',
        'total_linea' => 'decimal:2',
    ];

    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(Presupuesto::class);
    }
}
