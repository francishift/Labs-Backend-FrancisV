<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

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

    /**
     * Obtiene los datos del Top 10 clientes por valor anualizado (Proyectos + Mantenimientos).
     */
    public static function getAnnualValueDataForChart()
    {
        $clientesData = [];

        // Proyectos activos
        Proyecto::where('estado', 'En proceso')
            ->with('client:id,name')
            ->get()
            ->each(function ($p) use (&$clientesData) {
                $name = $p->client->name ?? 'Sin Cliente';
                $clientesData[$name] = ($clientesData[$name] ?? 0) + (float)$p->presupuesto;
            });

        // Mantenimientos activos
        Mantenimiento::where('estado', 'en curso')
            ->with('cliente:id,name')
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
