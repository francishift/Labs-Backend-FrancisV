<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $cif_nif
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $mobile
 * @property string|null $address
 * @property string|null $city
 * @property string|null $zip_code
 * @property string|null $province
 * @property string|null $country
 * @property \Illuminate\Support\Carbon|null $excel_created_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read float $active_projects_budget
 * @property-read float $monthly_maintenance_income
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Proyecto[] $proyectos
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Mantenimiento[] $mantenimientos
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Factura[] $facturas
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Client extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats'));
    }

    protected $fillable = [
        'name',
        'cif_nif',
        'email',
        'phone',
        'mobile',
        'address',
        'city',
        'zip_code',
        'province',
        'country',
        'excel_created_at',
    ];

    protected $casts = [
        'excel_created_at' => 'datetime',
    ];

    public function proyectos()
    {
        return $this->hasMany(Proyecto::class);
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class);
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'client_id');
    }


    /**
     * Presupuesto total de proyectos en proceso.
     */
    public function getActiveProjectsBudgetAttribute(): float
    {
        return (float) $this->proyectos->where('estado', 'En proceso')->sum('presupuesto');
    }

    /**
     * Ingreso mensual total de mantenimientos en curso (anuales prorrateados).
     */
    public function getMonthlyMaintenanceIncomeAttribute(): float
    {
        return (float) $this->mantenimientos->where('estado', 'en curso')->reduce(function ($acc, $m) {
            $amount = (float) $m->importe;
            return $acc + ($m->tipo_pago === 'anual' ? $amount / 12 : $amount);
        }, 0);
    }

    /**
     * Obtiene los datos del Top 10 clientes por valor anualizado (Proyectos + Mantenimientos).
     */
    public static function getAnnualValueDataForChart($year = null)
    {
        $year = $year ?: now()->year;
        $clientesData = [];

        // Proyectos activos o finalizados este año
        Proyecto::where('estado', 'En proceso')
            ->orWhere(function ($q) use ($year) {
                // Se consideran solo los finalizados en este año
                $q->where('estado', 'Finalizado')
                  ->whereYear('fecha_fin', $year);
            })
            ->with(['client:id,name', 'extensiones:id,nombre']) // Cargar relaciones necesarias
            ->get()
            ->each(function ($p) use (&$clientesData) {
                $name = $p->client->name ?? 'Sin Cliente';
                $clientesData[$name] = ($clientesData[$name] ?? 0) + (float)$p->presupuesto;
            });

        // Mantenimientos activos
        Mantenimiento::where('estado', 'en curso')
            ->with(['cliente:id,name', 'extensiones:id,nombre', 'precios']) // Added precios
            ->get()
            ->each(function ($m) use (&$clientesData) {
                $name = $m->cliente->name ?? 'Sin Cliente';
                $clientesData[$name] = ($clientesData[$name] ?? 0) + $m->calculatePeriodIncome('all');
            });

        return collect($clientesData)
            ->map(fn($value, $name) => ['name' => $name, 'value' => $value])
            ->sortByDesc('value')
            ->values()
            ->take(10);
    }
}
