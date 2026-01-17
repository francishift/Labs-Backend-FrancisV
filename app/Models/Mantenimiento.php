<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HandlesExtensionSnapshots;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mantenimiento extends Model
{
    use HasFactory, HandlesExtensionSnapshots;

    protected static function booted()
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats'));
    }

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
        'precio_hora',
        'porcentaje_software',
        'coste_software_anual',
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

    /**
     * Scope para mantenimientos en curso.
     */
    public function scopeActive($query)
    {
        return $query->where('estado', 'en curso');
    }

    /**
     * Scope para mantenimientos finalizados en el año actual.
     */
    public function scopeFinishedThisYear($query, $year = null)
    {
        $year = $year ?: date('Y');
        return $query->where('estado', 'finalizado')
            ->whereYear('fecha_fin', $year);
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
    public function calculatePeriodIncome($period = 'all')
    {
        $importe = (float) $this->importe;
        $isAll = $period === 'all';

        if ($this->tipo_pago === 'mensual') {
            return $isAll ? $importe * 12 : $importe;
        }

        if ($this->tipo_pago === 'trimestral') {
            $mensual = $importe / 3;
            return $isAll ? $mensual * 12 : $mensual;
        }

        if ($this->tipo_pago === 'anual') {
            $mensual = $importe / 12;
            return $isAll ? $mensual * 12 : $mensual;
        }

        return 0;
    }


    /**
     * Obtiene los datos financieros para un periodo específico.
     */
    public function getFinancialStats($month = 'all', $year = null)
    {
        $year = $year ?: date('Y');
        
        // Coste de servicios
        $precioHora = $this->precio_hora ?: self::getDiscountedHourlyRate();
        $minutos = $this->servicios()
            ->whereYear('fecha', $year)
            ->when($month !== 'all', fn($q) => $q->whereMonth('fecha', $month))
            ->sum('duracion_minutos');
            
        $costeServicios = ($minutos / 60) * $precioHora;

        // Coste de extensiones
        $costeExtensiones = $this->extensiones->sum(function($ext) use ($month) {
            $precioSnapshot = $ext->pivot->precio_aplicado ?? null;
            return $ext->calculatePeriodCost($month, $precioSnapshot ? (float)$precioSnapshot : null);
        });

        // Coste de software/hosting (Global Overhead - Snapshot or Current)
        $totalSoftwareAnual = $this->coste_software_anual ?? Software::getTotalAnual();
        $porcentajeSoftware = $this->porcentaje_software ?? (float) Configuracion::get('porcentaje_software', 2);
        $costeSoftwareTotal = ($totalSoftwareAnual * $porcentajeSoftware) / 100;

        $costeSoftware = $month === 'all' ? $costeSoftwareTotal : ($costeSoftwareTotal / 12);

        $ingreso = $this->calculatePeriodIncome($month);

        return [
            'ingreso' => $ingreso,
            'coste_servicios' => $costeServicios,
            'coste_extensiones' => $costeExtensiones,
            'coste_software' => $costeSoftware,
            'balance' => $ingreso - $costeServicios - $costeExtensiones - $costeSoftware,
            'minutos' => $minutos
        ];
    }

    /**
     * Datos para el gráfico de mantenimientos activos.
     */
    public static function getActiveDataForChart()
    {
        return self::active()
            ->get()
            ->map(fn($m) => [
                'name' => $m->aplicacion,
                'value' => (float) $m->calculatePeriodIncome('all')
            ])
            ->sortByDesc('value')
            ->values();
    }

    /**
     * Estadísticas rápidas para el dashboard.
     */
    public static function getDashboardStats($month)
    {
        $mantenimientos = self::active()->get();
        return [
            'total_mes' => $mantenimientos->sum(fn($m) => $m->calculatePeriodIncome($month)),
            'count' => $mantenimientos->count()
        ];
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

    /**
     * Obtiene estadísticas agregadas anuales de todos los mantenimientos activos.
     */
    public static function getAggregatedStatsForYear($year = null)
    {
        $year = $year ?: date('Y');
        $mantenimientos = self::active()
            ->orWhere(fn($q) => $q->finishedThisYear($year))
            ->get();
       
        $totalIngresos = 0;
        $totalFijo = 0;
        $totalSoftware = 0;
        $totalMinutos = 0;

        foreach ($mantenimientos as $mantenimiento) {
            $stats = $mantenimiento->getFinancialStats('all', $year);
            $totalIngresos += $stats['ingreso'];
            $totalFijo += $stats['coste_extensiones'] + $stats['coste_software'];
            $totalSoftware += $stats['coste_software'];
            $totalMinutos += $stats['minutos'];
        }

        return [
            'total_ingresos' => $totalIngresos,
            'total_fijo' => $totalFijo,
            'total_software' => $totalSoftware,
            'total_minutos' => $totalMinutos,
            'total_gastos' => $totalFijo + ($totalMinutos / 60 * self::getDiscountedHourlyRate()),
        ];
    }
}
