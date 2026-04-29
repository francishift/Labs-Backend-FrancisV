<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\Mantenimiento;
use App\Models\Extension;
use App\Models\Client;
use App\Models\Presupuesto;
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

        // Caché de estadísticas para optimizar el dashboard
        $dashboardData = \Illuminate\Support\Facades\Cache::remember('admin_dashboard_stats', 3600, function () use ($now, $currentMonth, $currentYear) {
            $proyectoStats = Proyecto::getActiveStats();
            $finishedStats = Proyecto::getFinishedStats($currentYear);
            $mantenimientoStats = Mantenimiento::getDashboardStats($currentMonth);
            $financeMetrics = app(\App\Services\FinanceService::class)->getCurrentDashboardMetrics();

            return [
                'stats' => [
                    'proyectos_en_proceso' => $proyectoStats['count'],
                    'proyectos_finalizados_anio' => $finishedStats['count'],
                    'presupuesto_finalizado_anio' => $finishedStats['presupuesto'],
                    'total_mantenimiento_mes' => $mantenimientoStats['total_mes'],
                    'presupuesto_total_activo' => $proyectoStats['presupuesto'],
                    'mes_actual' => ucfirst($now->translatedFormat('F')),
                    'anio_actual' => $currentYear,
                ],
                'charts' => [
                    'proyectos_activos' => Proyecto::getActiveDataForChart(),
                    'valor_mantenimientos' => Mantenimiento::getActiveDataForChart(),
                    'valor_por_cliente' => Client::getAnnualValueDataForChart(),
                    'uso_extensiones' => Extension::getUsageStatsForChart(),
                ],
                'finances' => $financeMetrics,
            ];
        });

        return Inertia::render('Dashboard', $dashboardData);
    }
}
