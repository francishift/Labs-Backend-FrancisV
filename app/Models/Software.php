<?php

namespace App\Models;

use App\Traits\CalculatesPeriodCosts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Software extends Model
{
    use HasFactory, CalculatesPeriodCosts, SoftDeletes;

    protected static function booted()
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats'));
    }

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

    public static function getAggregatedYearlyStats($year = null)
    {
        $activeSoftwares = self::where('estado', 'Activa')->get();
        $costeAnual = $activeSoftwares->sum(fn($s) => $s->calculatePeriodCost('all'));
        $costeMensual = $activeSoftwares->sum(fn($s) => $s->calculatePeriodCost(date('m')));

        return [
            'costo_mensual' => $costeMensual,
            'costo_anual' => $costeAnual,
        ];
    }
}
