<?php

namespace App\Models;

use App\Traits\CalculatesPeriodCosts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Software extends Model
{
    use HasFactory, CalculatesPeriodCosts, SoftDeletes;

    protected $table = 'softwares';

    protected $fillable = [
        'tipo',
        'nombre',
        'descripcion',
        'tipo_licencia',
        'precio',
        'estado',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    protected static ?float $totalAnualCache = null;

    public static function getTotalAnual()
    {
        if (self::$totalAnualCache !== null) {
            return self::$totalAnualCache;
        }

        self::$totalAnualCache = (float) self::whereIn('estado', ['Activa', 'activo'])
            ->get()
            ->sum(fn($s) => $s->calculatePeriodCost('all'));

        return self::$totalAnualCache;
    }

    /**
     * Obtiene estadísticas agregadas de software combinando ingresos (cobros) y gastos (costos).
     */
    public static function getAggregatedYearlyStats($year = null)
    {
        $year = $year ?: date('Y');
        
        $activeSoftwares = self::where('estado', 'Activa')->get();
        $costeAnual = $activeSoftwares->sum(fn($s) => $s->calculatePeriodCost('all'));
        $costeMensual = $activeSoftwares->sum(fn($s) => $s->calculatePeriodCost(date('m')));

        $proyectoStats = Proyecto::getAggregatedStatsForYear($year);
        $mantenimientoStats = Mantenimiento::getAggregatedStatsForYear($year);

        $cobroAnual = $proyectoStats['total_software'] + $mantenimientoStats['total_software'];
        $cobroMensual = $cobroAnual / 12;

        return [
            'cobro_mensual' => $cobroMensual,
            'cobro_anual' => $cobroAnual,
            'costo_mensual' => $costeMensual,
            'costo_anual' => $costeAnual,
        ];
    }
}
