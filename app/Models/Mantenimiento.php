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
        'precio_hora',
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
        return $this->belongsToMany(Extension::class, 'mantenimiento_extension', 'mantenimiento_id', 'extension_id')->withPivot('precio_aplicado')->withTimestamps();
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
        if ($period === 'all') {
            return $this->tipo_pago === 'anual' ? $this->importe : ($this->tipo_pago === 'trimestral' ? $this->importe * 4 : $this->importe * 12);
        }

        $month = (int)$period;
        if ($this->tipo_pago === 'mensual') return $this->importe;
        if ($this->tipo_pago === 'trimestral') {
            $months = match ($this->fecha_inicio->month % 3 ?: 3) {
                1 => [$this->fecha_inicio->month, $this->fecha_inicio->month + 3, $this->fecha_inicio->month + 6, $this->fecha_inicio->month + 9],
                2 => [$this->fecha_inicio->month, $this->fecha_inicio->month + 3, $this->fecha_inicio->month + 6, $this->fecha_inicio->month + 9],
                0 => [$this->fecha_inicio->month, $this->fecha_inicio->month + 3, $this->fecha_inicio->month + 6, $this->fecha_inicio->month + 9],
            };
            // Simplificación: si el mes coincide con el trimestre de inicio o sus múltiplos
            return (($month - $this->fecha_inicio->month) % 3 === 0) ? $this->importe : 0;
        }
        if ($this->tipo_pago === 'anual') {
            return $month === $this->fecha_inicio->month ? $this->importe : 0;
        }

        return 0;
    }

    /**
     * Sincroniza extensiones capturando su precio actual como snapshot.
     */
    public function syncExtensionSnapshots(array $extensionIds)
    {
        $extensionesConPrecio = [];
        foreach ($extensionIds as $extId) {
            $ext = Extension::find($extId);
            if ($ext) {
                $extensionesConPrecio[$extId] = ['precio_aplicado' => $ext->precio];
            }
        }
        return $this->extensiones()->sync($extensionesConPrecio);
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
            $precio = $ext->pivot->precio_aplicado ?? $ext->precio;
            $esAnual = $ext->tipo_licencia === 'anual';
            
            if ($month === 'all') {
                return $esAnual ? $precio : $precio * 12;
            }
            return $esAnual ? $precio / 12 : $precio;
        });

        $ingreso = $this->calculatePeriodIncome($month);

        return [
            'ingreso' => $ingreso,
            'coste_servicios' => $costeServicios,
            'coste_extensiones' => $costeExtensiones,
            'balance' => $ingreso - $costeServicios - $costeExtensiones,
            'minutos' => $minutos
        ];
    }

    /**
     * Datos para el gráfico de mantenimientos activos.
     */
    public static function getActiveDataForChart()
    {
        return self::where('estado', 'en curso')
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
        $mantenimientos = self::where('estado', 'en curso')->get();
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
}
