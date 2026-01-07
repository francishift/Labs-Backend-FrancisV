<?php

namespace App\Models;

use App\Traits\CalculatesPeriodCosts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Extension extends Model
{
    use HasFactory, CalculatesPeriodCosts, SoftDeletes;

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
        
        return self::withTrashed()
            ->withCount(['proyectos', 'mantenimientos'])
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

    /**
     * Obtiene estadísticas agregadas de extensiones combinando proyectos y mantenimientos.
     */
    public static function getAggregatedYearlyStats($year = null)
    {
        $year = $year ?: date('Y');
        
        $proyectoStats = Proyecto::getAggregatedStatsForYear($year);
        $mantenimientoStats = Mantenimiento::getAggregatedStatsForYear($year);

        $totalAnual = $proyectoStats['total_fijo'] + $mantenimientoStats['total_fijo'];
        
        return [
            'total_anual' => $totalAnual,
            'total_mensual' => $totalAnual / 12,
        ];
    }
}
