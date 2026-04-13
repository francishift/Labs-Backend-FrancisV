<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HandlesExtensionSnapshots;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Mantenimiento extends Model
{
    use HasFactory, HandlesExtensionSnapshots;

    protected static function booted()
    {
        static::saved(function ($mantenimiento) {
            \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats');
            
            // Registrar cambios de precio/pago automáticamente
            if ($mantenimiento->wasRecentlyCreated || $mantenimiento->wasChanged(['importe', 'tipo_pago'])) {
                $mantenimiento->precios()->create([
                    'importe' => $mantenimiento->importe,
                    'tipo_pago' => $mantenimiento->tipo_pago,
                    'fecha_aplicacion' => $mantenimiento->wasRecentlyCreated 
                        ? ($mantenimiento->fecha_inicio ?: now()->startOfMonth()) 
                        : now()->startOfMonth(),
                ]);
            }

            // Si cambia el estado (de en curso a finalizado o viceversa), recálculo
            if ($mantenimiento->wasChanged('estado')) {
                $extensionIds = $mantenimiento->extensiones()->pluck('extensiones.id')->toArray();
                if (!empty($extensionIds)) {
                    app(\App\Services\ExtensionPricingService::class)->recalculateForMultiple($extensionIds);
                }
            }
        });

        static::deleting(function ($model) {
            $model->temporal_extension_ids = $model->extensiones()->pluck('extensiones.id')->toArray();
        });

        static::deleted(function ($model) {
            \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats');

            if (!empty($model->temporal_extension_ids)) {
                app(\App\Services\ExtensionPricingService::class)->recalculateForMultiple($model->temporal_extension_ids);
            }
        });
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
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_fin' => 'date:Y-m-d',
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

    public function precios(): HasMany
    {
        return $this->hasMany(MantenimientoPrecio::class)->orderBy('fecha_aplicacion', 'desc');
    }

    public function calculatePeriodIncome($period = 'all', $year = null)
    {
        $year = $year ?: now()->year;

        if ($period === 'all') {
            // Si las relaciones están cargadas, calculamos en memoria para evitar N+1
            $totalYear = 0;
            for ($m = 1; $m <= 12; $m++) {
                $totalYear += $this->calculateMonthIncome($m, $year);
            }
            return $totalYear;
        }

        return $this->calculateMonthIncome((int)$period, $year);
    }

    /**
     * Calcula el ingreso para un mes específico basado en el histórico de precios.
     */
    protected function calculateMonthIncome(int $month, int $year)
    {
        $date = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $dateStr = $date->format('Y-m-d');
        
        // Si el mes consultado es anterior a la fecha de inicio, el ingreso es 0
        if ($this->fecha_inicio && $date->isBefore($this->fecha_inicio->copy()->startOfMonth())) {
            return 0;
        }

        // Si el mes consultado es posterior a la fecha de fin, el ingreso es 0
        if ($this->fecha_fin && $date->isAfter($this->fecha_fin->copy()->endOfMonth())) {
            return 0;
        }

        // Buscar el precio vigente para este mes
        // OPTIMIZACIÓN: Si la relación 'precios' ya está cargada (eager loading), usamos la colección en memoria
        if ($this->relationLoaded('precios')) {
            $p = $this->precios
                ->where('fecha_aplicacion', '<=', $dateStr)
                ->sortByDesc('fecha_aplicacion')
                ->first();
        } else {
            $p = $this->precios()
                ->where('fecha_aplicacion', '<=', $dateStr)
                ->first();
        }

        if (!$p) {
            $importe = (float) $this->importe;
            $tipo_pago = $this->tipo_pago;
        } else {
            $importe = (float) $p->importe;
            $tipo_pago = $p->tipo_pago;
        }

        if ($tipo_pago === 'mensual') {
            return $importe;
        }

        if ($tipo_pago === 'trimestral') {
            return $importe / 3;
        }

        if ($tipo_pago === 'anual') {
            return $importe / 12;
        }

        return 0;
    }


    /**
     * Obtiene los datos financieros para un periodo específico.
     */
    public function getFinancialStats($month = 'all', $year = null)
    {
        $year = $year ?: now()->year;
        
        $precioHoraFallback = $this->precio_hora ?: self::getDiscountedHourlyRate();
        
        $minutos = 0;
        $costeServicios = 0;

        // OPTIMIZACIÓN: Si 'servicios' está cargado, filtramos en memoria
        if ($this->relationLoaded('servicios')) {
            $serviciosFiltrados = $this->servicios
                ->filter(function($s) use ($year, $month) {
                    $fecha = Carbon::parse($s->fecha);
                    $matchYear = $fecha->year === (int)$year;
                    if (!$matchYear) return false;
                    if ($month !== 'all' && $fecha->month !== (int)$month) return false;
                    return true;
                });
                
            $minutos = $serviciosFiltrados->sum('duracion_minutos');
            $costeServicios = $serviciosFiltrados->sum(function($s) use ($precioHoraFallback) {
                $precio = $s->precio_hora !== null ? (float)$s->precio_hora : $precioHoraFallback;
                return ($s->duracion_minutos / 60) * $precio;
            });
        } else {
            $query = $this->servicios()
                ->whereYear('fecha', $year)
                ->when($month !== 'all', fn($q) => $q->whereMonth('fecha', $month));
                
            $minutos = $query->sum('duracion_minutos');
            
            // Calculate directly in DB if possible, or fetch and sum
            $costeServicios = $query->get()->sum(function($s) use ($precioHoraFallback) {
                $precio = $s->precio_hora !== null ? (float)$s->precio_hora : $precioHoraFallback;
                return ($s->duracion_minutos / 60) * $precio;
            });
        }

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

        $ingreso = $this->calculatePeriodIncome($month, $year);

        // Obtener el registro de precio aplicable al final del periodo para info visual
        $effectiveMonth = $month === 'all' ? 12 : (int)$month;
        $date = Carbon::createFromDate($year, $effectiveMonth, 1)->endOfMonth();
        $p = $this->precios()
            ->where('fecha_aplicacion', '<=', $date->format('Y-m-d'))
            ->first();

        return [
            'ingreso' => $ingreso,
            'tipo_pago' => $p ? $p->tipo_pago : $this->tipo_pago,
            'importe' => $p ? (float)$p->importe : (float)$this->importe,
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
        return self::where('estado', 'en curso')
            ->with(['precios', 'cliente'])
            ->get()
            ->map(fn($m) => [
                'name' => $m->aplicacion . ' (' . ($m->cliente->name ?? 'S/N') . ')',
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
        $mantenimientos = self::where('estado', 'en curso')
            ->with('precios') // Eager loading
            ->get();
            
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
        $year = $year ?: now()->year;
        // EAGER LOADING: Cargamos todas las relaciones necesarias para evitar N+1 en el bucle
        $mantenimientos = self::active()
            ->orWhere(fn($q) => $q->finishedThisYear($year))
            ->with(['precios', 'servicios', 'extensiones'])
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
