<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mantenimiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'aplicacion',
        'url',
        'client_id',
        'fecha_inicio',
        'fecha_fin',
        'tipo_pago',
        'importe',
        'estado',
        'descripcion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'importe' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function extensiones(): BelongsToMany
    {
        return $this->belongsToMany(Extension::class, 'mantenimiento_extension', 'mantenimiento_id', 'extension_id');
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(MantenimientoServicio::class);
    }

    /**
     * Calcula el ingreso proporcional para un periodo dado.
     * 
     * @param string|int $month 'all' para anual o 1-12 para un mes específico.
     * @return float
     */
    public function calculatePeriodIncome($month = 'all'): float
    {
        $importe = (float) $this->importe;
        $esAnual = $this->tipo_pago === 'anual';

        if ($month === 'all') {
            return $esAnual ? $importe : $importe * 12;
        }

    return $esAnual ? $importe / 12 : $importe;
    }

    /**
     * Devuelve el precio por hora con el descuento de mantenimiento aplicado.
     * 
     * @return float
     */
    public static function getDiscountedHourlyRate(): float
    {
        $precioHora = (float) Configuracion::get('precio_hora', 0);
        $descuento = (float) Configuracion::get('descuento_mantenimiento', 0);
        
        return $precioHora * (1 - ($descuento / 100));
    }
}
