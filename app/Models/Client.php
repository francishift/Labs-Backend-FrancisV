<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'contact',
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
    public static function getAnnualValueDataForChart()
    {
        $clientesData = [];

        // Proyectos activos
        Proyecto::where('estado', 'En proceso')
            ->with(['client:id,name', 'extensiones:id,nombre']) // Cargar relaciones necesarias
            ->get()
            ->each(function ($p) use (&$clientesData) {
                $name = $p->client->name ?? 'Sin Cliente';
                $clientesData[$name] = ($clientesData[$name] ?? 0) + (float)$p->presupuesto;
            });

        // Mantenimientos activos
        Mantenimiento::where('estado', 'en curso')
            ->with(['cliente:id,name', 'extensiones:id,nombre']) // Cargar relaciones necesarias
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
