<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Extension extends Model
{
    use HasFactory;

    protected $table = 'extensiones';

    protected $fillable = [
        'nombre',
        'url',
        'descripcion',
        'precio',
        'tipo_licencia',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    /**
     * Calcula el coste de la extensión para un periodo dado.
     * 
     * @param string|int $month 'all' para anual o 1-12 para un mes específico.
     * @return float
     */
    public function calculatePeriodCost($month = 'all'): float
    {
        $precio = (float) $this->precio;
        $tipo = strtolower($this->tipo_licencia);

        if ($month === 'all') {
            // Vista anual
            if ($tipo === 'anual') return $precio;
            if ($tipo === 'mensual') return $precio * 12;
            return $precio; // Pago único o desconocido
        } else {
            // Vista mensual
            if ($tipo === 'anual') return $precio / 12;
            if ($tipo === 'mensual') return $precio;
            return $precio / 12; // Pago único - lo prorrateamos por 12
        }
    }

    public function proyectos()
    {
        return $this->belongsToMany(Proyecto::class, 'proyecto_extension');
    }

    public function mantenimientos()
    {
        return $this->belongsToMany(Mantenimiento::class, 'mantenimiento_extension', 'extension_id', 'mantenimiento_id')->withPivot('precio_aplicado')->withTimestamps();
    }

    /**
     * Obtiene estadísticas de uso de extensiones para el dashboard.
     */
    public static function getUsageStatsForDashboard()
    {
        $totalEntidades = Proyecto::count() + Mantenimiento::count();
        
        return self::withCount(['proyectos', 'mantenimientos'])
            ->get()
            ->map(function ($ext) use ($totalEntidades) {
                $totalUso = $ext->proyectos_count + $ext->mantenimientos_count;
                return [
                    'name' => $ext->nombre,
                    'value' => $totalUso,
                    'percentage' => $totalEntidades > 0 ? round(($totalUso / $totalEntidades) * 100, 1) : 0
                ];
            })
            ->filter(fn($item) => $item['value'] > 0)
            ->sortByDesc('value')
            ->values();
    }
}
