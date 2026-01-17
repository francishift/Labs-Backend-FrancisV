<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HandlesExtensionSnapshots;
use App\Models\Configuracion;
use App\Models\Software;

class Proyecto extends Model
{
    use HasFactory, HandlesExtensionSnapshots;

    protected static function booted()
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats'));
    }

    protected $fillable = [
        'proyecto',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'presupuesto',
        'estado',
        'client_id',
        'precio_hora',
        'porcentaje_software',
        'coste_software_anual',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'presupuesto' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function servicios()
    {
        return $this->hasMany(Servicio::class);
    }

    /**
     * Scope para proyectos en proceso.
     */
    public function scopeActive($query)
    {
        return $query->where('estado', 'En proceso');
    }

    /**
     * Scope para proyectos finalizados en el año actual.
     */
    public function scopeFinishedThisYear($query, $year = null)
    {
        $year = $year ?: date('Y');
        return $query->where('estado', 'Finalizado')
            ->whereYear('fecha_fin', $year);
    }


    /**
     * Datos para el gráfico de proyectos activos.
     */
    public static function getActiveDataForChart()
    {
        return self::where('estado', 'En proceso')
            ->select('proyecto', 'presupuesto')
            ->get()
            ->map(fn($p) => [
                'name' => $p->proyecto,
                'value' => (float) $p->presupuesto
            ]);
    }

    /**
     * Estadísticas de proyectos finalizados para el dashboard.
     */
    public static function getFinishedStats($year)
    {
        $query = self::where('estado', 'Finalizado')
            ->where(function ($q) use ($year) {
                $q->whereYear('fecha_fin', $year)
                    ->orWhere(function ($sub) use ($year) {
                        $sub->whereNull('fecha_fin')->whereYear('updated_at', $year);
                    });
            });

        return [
            'count' => $query->count(),
            'presupuesto' => $query->sum('presupuesto')
        ];
    }

    /**
     * Estadísticas de proyectos activos.
     */
    public static function getActiveStats()
    {
        return [
            'count' => self::where('estado', 'En proceso')->count(),
            'presupuesto' => self::where('estado', 'En proceso')->sum('presupuesto')
        ];
    }

    /**
     * Obtiene estadísticas agregadas detalladas de todos los proyectos activos.
     */
    public static function getAggregatedStatsForYear($year = null)
    {
        $year = $year ?: date('Y');
        $proyectos = self::active()
            ->orWhere(fn($q) => $q->finishedThisYear($year))
            ->with(['extensiones', 'servicios'])
            ->get();
        
        $totalPresupuesto = $proyectos->sum('presupuesto');
        $totalExtensiones = 0;
        $totalSoftware = 0;
        $totalServicios = 0;
        $totalMinutos = 0;

        foreach ($proyectos as $proyecto) {
            // Extensiones
            foreach ($proyecto->extensiones as $ext) {
                $totalExtensiones += (float) ($ext->pivot->precio_aplicado ?? $ext->precio);
            }

            // Software (Snapshot or Current)
            $softAnual = (float) ($proyecto->coste_software_anual ?? Software::getTotalAnual());
            $porcentaje = (float) ($proyecto->porcentaje_software ?? Configuracion::get('porcentaje_software', 2));
            $totalSoftware += ($softAnual * $porcentaje) / 100;

            // Servicios
            foreach ($proyecto->servicios as $servicio) {
                $totalMinutos += $servicio->duracion_minutos;
                $totalServicios += ($servicio->duracion_minutos / 60) * ($proyecto->precio_hora ?: 0);
            }
        }

        return [
            'total_presupuesto' => $totalPresupuesto,
            'total_fijo' => $totalExtensiones + $totalSoftware,
            'total_software' => $totalSoftware,
            'total_extensiones' => $totalExtensiones,
            'total_servicios' => $totalServicios,
            'total_minutos' => $totalMinutos,
            'total_gastos' => $totalExtensiones + $totalSoftware + $totalServicios,
        ];
    }

    /**
     * Obtiene los datos financieros detallados del proyecto.
     */
    public function getFinancialStats()
    {
        $servicesTotal = $this->servicios->reduce(function ($acc, $s) {
            $precioHora = $s->precio_hora ?? Configuracion::get('precio_hora', 0);
            return $acc + (($s->duracion_minutos / 60) * $precioHora) + ($s->precio ?? 0);
        }, 0);

        $extensionsTotal = $this->extensiones->reduce(function ($acc, $e) {
            return $acc + (float)($e->pivot->precio_aplicado ?? $e->precio ?? 0);
        }, 0);

        $totalSoftwareAnual = $this->coste_software_anual ?? Software::getTotalAnual();
        $porcentajeSoftware = (float)($this->porcentaje_software ?? Configuracion::get('porcentaje_software', 2));
        $costeSoftware = ($totalSoftwareAnual * $porcentajeSoftware) / 100;

        $grandTotal = $servicesTotal + $extensionsTotal + $costeSoftware;

        return [
            'servicesTotal' => $servicesTotal,
            'extensionsTotal' => $extensionsTotal,
            'costeSoftware' => $costeSoftware,
            'grandTotal' => $grandTotal,
        ];
    }
}
