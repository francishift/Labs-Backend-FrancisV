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
        'estado',
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
     * Obtiene estadísticas de uso de las extensiones para el dashboard.
     * 
     * Calcula en cuántos proyectos y mantenimientos activos (o finalizados en el año en curso) 
     * se está utilizando cada extensión, evitando problemas de N+1 mediante el uso de withCount().
     *
     * @param string|null $year Año para el filtrado, por defecto el actual.
     * @return array
     */
    public static function getUsageStatsForChart($year = null)
    {
        $year = $year ?: date('Y');

        // Contamos las relaciones en la propia consulta de BD. 
        // Condicionamos el conteo a proyectos y mantenimientos bajo los mismos criterios
        // que antes: que estén activos o que hayan finalizado en el año provisto.
        $extensiones = self::withCount([
            'proyectos' => function ($query) use ($year) {
                $query->active()->orWhere(fn($q) => $q->finishedThisYear($year));
            },
            'mantenimientos' => function ($query) use ($year) {
                $query->active()->orWhere(fn($q) => $q->finishedThisYear($year));
            }
        ])->get();

        $results = $extensiones->map(function ($ext) {
            $totalUsos = $ext->proyectos_count + $ext->mantenimientos_count;

            // Solo incluimos la extensión si tiene al menos 1 uso
            if ($totalUsos > 0) {
                return [
                    'name' => $ext->nombre,
                    'value' => $totalUsos,
                ];
            }
            return null;
        })
        ->filter()
        ->sortByDesc('value')
        ->values()
        ->all();

        return $results;
    }

    /**
     * Obtiene estadísticas agregadas de extensiones combinando proyectos y mantenimientos.
     */
    public static function getAggregatedYearlyStats($year = null)
    {
        // El verdadero coste bruto de licencias activas de extensiones para la empresa
        $extensionesActivas = self::where('estado', 'Activada')->get();
        $totalAnual = $extensionesActivas->sum(fn($e) => $e->calculatePeriodCost('all'));
        $totalMensual = $extensionesActivas->sum(fn($e) => $e->calculatePeriodCost(date('m')));
        
        return [
            'total_anual' => $totalAnual,
            'total_mensual' => $totalMensual,
        ];
    }
}
