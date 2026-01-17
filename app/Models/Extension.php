<?php

namespace App\Models;

use App\Traits\CalculatesPeriodCosts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Extension extends Model
{
    use HasFactory, CalculatesPeriodCosts, SoftDeletes;

    protected static function booted()
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats'));
    }

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
     * Obtiene estadísticas financieras (repercutido) de extensiones y software para el dashboard.
     */
    public static function getFinancialStatsForChart($year = null)
    {
        $year = $year ?: date('Y');
        
        $proyectos = Proyecto::active()
            ->orWhere(fn($q) => $q->finishedThisYear($year))
            ->with('extensiones')
            ->get();
            
        $mantenimientos = Mantenimiento::active()
            ->orWhere(fn($q) => $q->finishedThisYear($year))
            ->with('extensiones')
            ->get();
            
        $results = [];
        $totalSoftware = 0;
        
        foreach ($proyectos as $p) {
            foreach ($p->extensiones as $ext) {
                $valor = (float) ($ext->pivot->precio_aplicado ?? $ext->precio);
                $results[$ext->nombre] = ($results[$ext->nombre] ?? 0) + $valor;
            }
            $totalSoftware += ($p->coste_software_anual * $p->porcentaje_software) / 100;
        }
        
        foreach ($mantenimientos as $m) {
            foreach ($m->extensiones as $ext) {
                $valor = (float) ($ext->pivot->precio_aplicado ?? $ext->precio);
                $results[$ext->nombre] = ($results[$ext->nombre] ?? 0) + $valor;
            }
            $totalSoftware += ($m->coste_software_anual * $m->porcentaje_software) / 100;
        }
        
        if ($totalSoftware > 0) {
            $results['Software/Hosting'] = $totalSoftware;
        }
        
        return collect($results)
            ->map(fn($val, $key) => [
                'name' => $key,
                'value' => round($val, 2)
            ])
            ->sortByDesc('value')
            ->values()
            ->all();
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
