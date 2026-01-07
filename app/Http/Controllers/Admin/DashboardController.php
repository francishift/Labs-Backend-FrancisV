<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\Mantenimiento;
use App\Models\Extension;
use App\Models\Client;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // 1. Proyectos en proceso y su presupuesto total
        $proyectosEnProceso = \App\Models\Proyecto::where('estado', 'En proceso')->count();
        $presupuestoTotalActivo = \App\Models\Proyecto::where('estado', 'En proceso')->sum('presupuesto');

        // 2. Proyectos finalizados en el año actual y su presupuesto total
        $proyectosFinalizadosAnioQuery = \App\Models\Proyecto::where('estado', 'Finalizado')
            ->where(function ($query) use ($currentYear) {
                $query->whereYear('fecha_fin', $currentYear)
                    ->orWhere(function ($q) use ($currentYear) {
                        $q->whereNull('fecha_fin')
                            ->whereYear('updated_at', $currentYear);
                    });
            });

        $proyectosFinalizadosAnio = $proyectosFinalizadosAnioQuery->count();
        $presupuestoFinalizadoAnio = $proyectosFinalizadosAnioQuery->sum('presupuesto');
        $mantenimientos = \App\Models\Mantenimiento::where('estado', 'en curso')->get();
        $totalMantenimientoMes = 0;

        foreach ($mantenimientos as $mantenimiento) {
            $totalMantenimientoMes += $mantenimiento->calculatePeriodIncome($currentMonth);
        }

        // 4. Datos para gráfico: Valor Anual de Mantenimientos Activos
        $mantenimientosActivosData = \App\Models\Mantenimiento::where('estado', 'en curso')
            ->select('aplicacion', 'importe', 'tipo_pago')
            ->get()
            ->map(function ($m) {
                return [
                    'name' => $m->aplicacion,
                    'value' => (float) $m->calculatePeriodIncome('all')
                ];
            })
            ->sortByDesc('value')
            ->values();

        // Datos para gráfico: Proyectos Activos y su Valor
        $proyectosActivosData = \App\Models\Proyecto::where('estado', 'En proceso')
            ->select('proyecto', 'presupuesto')
            ->get()
            ->map(function ($p) {
                return [
                    'name' => $p->proyecto,
                    'value' => (float) $p->presupuesto
                ];
            });

        // Datos para gráfico: Uso de Extensiones
        $totalEntidades = \App\Models\Proyecto::count() + \App\Models\Mantenimiento::count();
        $usoExtensiones = \App\Models\Extension::withCount(['proyectos', 'mantenimientos'])
            ->get()
            ->map(function ($ext) use ($totalEntidades) {
                $totalUso = $ext->proyectos_count + $ext->mantenimientos_count;
                return [
                    'name' => $ext->nombre,
                    'value' => $totalUso,
                    'percentage' => $totalEntidades > 0 ? round(($totalUso / $totalEntidades) * 100, 1) : 0
                ];
            })
            ->filter(fn($item) => $item['value'] > 0)
            ->sortByDesc('value')
            ->values();

        // 5. Datos para gráfico: Valor Total por Cliente (Anualizado)
        $clientesData = [];
        
        // Proyectos activos por cliente
        $proyectosPorCliente = \App\Models\Proyecto::where('estado', 'En proceso')
            ->with('client:id,name')
            ->get();
        
        foreach ($proyectosPorCliente as $p) {
            $clientName = $p->client->name ?? 'Sin Cliente';
            $clientesData[$clientName] = ($clientesData[$clientName] ?? 0) + (float)$p->presupuesto;
        }
        
        // Mantenimientos activos por cliente
        $mantenimientosPorCliente = \App\Models\Mantenimiento::where('estado', 'en curso')
            ->with('cliente:id,name')
            ->get();
            
        foreach ($mantenimientosPorCliente as $m) {
            $clientName = $m->cliente->name ?? 'Sin Cliente';
            $clientesData[$clientName] = ($clientesData[$clientName] ?? 0) + $m->calculatePeriodIncome('all');
        }
        
        $valorPorCliente = collect($clientesData)
            ->map(fn($value, $name) => ['name' => $name, 'value' => $value])
            ->sortByDesc('value')
            ->values()
            ->take(10);

        return Inertia::render('Dashboard', [
            'stats' => [
                'proyectos_en_proceso' => $proyectosEnProceso,
                'proyectos_finalizados_anio' => $proyectosFinalizadosAnio,
                'presupuesto_finalizado_anio' => $presupuestoFinalizadoAnio,
                'total_mantenimiento_mes' => $totalMantenimientoMes,
                'presupuesto_total_activo' => $presupuestoTotalActivo,
                'mes_actual' => ucfirst($now->translatedFormat('F')),
                'anio_actual' => $currentYear,
            ],
            'charts' => [
                'proyectos_activos' => $proyectosActivosData,
                'valor_mantenimientos' => $mantenimientosActivosData,
                'valor_por_cliente' => $valorPorCliente,
                'uso_extensiones' => $usoExtensiones,
            ]
        ]);
    }
}
