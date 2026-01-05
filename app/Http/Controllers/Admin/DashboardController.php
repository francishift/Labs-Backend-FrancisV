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
        $proyectosEnProceso = Proyecto::where('estado', 'En proceso')->count();
        $presupuestoTotalActivo = Proyecto::where('estado', 'En proceso')->sum('presupuesto');

        // 2. Proyectos finalizados en el año actual y su presupuesto total
        $proyectosFinalizadosAnioQuery = Proyecto::where('estado', 'Finalizado')
            ->where(function ($query) use ($currentYear) {
                $query->whereYear('fecha_fin', $currentYear)
                    ->orWhere(function ($q) use ($currentYear) {
                        $q->whereNull('fecha_fin')
                            ->whereYear('updated_at', $currentYear);
                    });
            });

        $proyectosFinalizadosAnio = $proyectosFinalizadosAnioQuery->count();
        $presupuestoFinalizadoAnio = $proyectosFinalizadosAnioQuery->sum('presupuesto');

        // 3. Mantenimientos a cobrar en el mes en curso
        $mantenimientos = Mantenimiento::where('estado', 'en curso')->get();
        $totalMantenimientoMes = 0;

        foreach ($mantenimientos as $mantenimiento) {
            $tipo = strtolower($mantenimiento->tipo_pago);
            $importe = (float) $mantenimiento->importe;
            
            if ($tipo === 'mensual') {
                $totalMantenimientoMes += $importe;
            } elseif ($tipo === 'anual') {
                $totalMantenimientoMes += ($importe / 12);
            }
        }

        // Datos para gráfico: Proyectos Activos y su Valor
        $proyectosActivosData = Proyecto::where('estado', 'En proceso')
            ->select('proyecto', 'presupuesto')
            ->get()
            ->map(function ($p) {
                return [
                    'name' => $p->proyecto,
                    'value' => (float) $p->presupuesto
                ];
            });

        // Datos para gráfico: Uso de Extensiones
        $totalEntidades = Proyecto::count() + Mantenimiento::count();
        $usoExtensiones = Extension::withCount(['proyectos', 'mantenimientos'])
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
                'uso_extensiones' => $usoExtensiones,
            ]
        ]);
    }
}
