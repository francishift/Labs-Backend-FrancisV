<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacturaLinea extends Model
{
    protected $fillable = [
        'factura_id',
        'concepto',
        'descripcion',
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

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }
}
