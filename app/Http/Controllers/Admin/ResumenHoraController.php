<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ResumenHoraController extends Controller
{
    /**
     * Display the resumen horas page.
     */
    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $clientId = $request->input('client_id');
        $tipoServicio = $request->input('tipo_servicio'); // 'proyectos', 'mantenimientos' o nulo (ambos)
        
        $filters = [
            'year' => (int)$year,
            'client_id' => $clientId,
            'tipo_servicio' => $tipoServicio,
        ];
        
        // Obtener configuración global una sola vez para evitar N+1
        $basePrecioHora = \App\Models\Configuracion::get('precio_hora', 0);
        $mantenimientoPrecioHora = \App\Models\Mantenimiento::getDiscountedHourlyRate();
        
        $serviciosProyecto = collect();
        $serviciosMantenimiento = collect();

        // Obtener servicios de proyectos si aplica el filtro
        // Obtener servicios de proyectos si aplica el filtro
        if (!$tipoServicio || $tipoServicio === 'proyectos') {
            $serviciosDb = \App\Models\Servicio::with('proyecto.client')
                ->whereYear('fecha', $year)
                ->when($clientId, function ($query, $clientId) {
                    $query->whereHas('proyecto', function ($q) use ($clientId) {
                        $q->where('client_id', $clientId);
                    });
                })
                ->orderBy('fecha', 'desc')
                ->get();
                
            $proyectosVistos = [];
            
            $serviciosProyecto = $serviciosDb->map(function ($s) use ($basePrecioHora, &$proyectosVistos) {
                $precioHora = $s->precio_hora ?: ($s->proyecto->precio_hora ?: $basePrecioHora);
                
                $esUltimoServicioDelAno = !in_array($s->proyecto_id, $proyectosVistos);
                if ($esUltimoServicioDelAno) {
                    $proyectosVistos[] = $s->proyecto_id;
                }

                return [
                    'id' => 'p_' . $s->id,
                    'proyecto_id' => $s->proyecto_id, // Para agrupar facturables
                    'fecha' => $s->fecha->format('Y-m-d'),
                    'mes' => $s->fecha->format('m'),
                    'mes_nombre' => ucfirst($s->fecha->translatedFormat('F')),
                    'tipo' => 'Proyecto',
                    'nombre' => $s->proyecto->proyecto ?? 'N/A',
                    'cliente' => $s->proyecto->client->name ?? 'N/A',
                    'descripcion' => $s->servicio,
                    'minutos' => $s->duracion_minutos,
                    'importe_horas' => ($s->duracion_minutos / 60) * $precioHora, // Costo
                    'ingreso_fijo' => $esUltimoServicioDelAno ? (float) ($s->proyecto->presupuesto ?? 0) : 0 
                ];
            });
        }

        // Obtener servicios de mantenimiento si aplica el filtro
        if (!$tipoServicio || $tipoServicio === 'mantenimientos') {
            $serviciosMantenimiento = \App\Models\MantenimientoServicio::with(['mantenimiento.cliente'])
                ->whereYear('fecha', $year)
                ->when($clientId, function ($query, $clientId) {
                    $query->whereHas('mantenimiento', function ($q) use ($clientId) {
                        $q->where('client_id', $clientId);
                    });
                })
                ->get()
                ->map(function ($s) use ($mantenimientoPrecioHora, $year) {
                    $precioHora = $s->precio_hora ?: ($s->mantenimiento->precio_hora ?: $mantenimientoPrecioHora);
                    
                    return [
                        'id' => 'm_' . $s->id,
                        'mantenimiento_id' => $s->mantenimiento_id, // Para agrupar
                        'fecha' => $s->fecha->format('Y-m-d'),
                        'mes' => $s->fecha->format('m'),
                        'tipo' => 'Mantenimiento',
                        'nombre' => $s->mantenimiento->aplicacion ?? 'N/A',
                        'cliente' => $s->mantenimiento->cliente->name ?? 'N/A',
                        'descripcion' => $s->descripcion,
                        'minutos' => $s->duracion_minutos,
                        'importe_horas' => ($s->duracion_minutos / 60) * $precioHora, // Costo
                    ];
                });
                
            // Obtener contratos activos para calcular los ingresos fijos pasivos mes a mes
            $mantenimientosContratos = \App\Models\Mantenimiento::with('precios')
                ->where(function ($q) use ($year) {
                    $startOfYear = "$year-01-01";
                    $endOfYear = "$year-12-31";
                     
                    $q->where(function($sub) use ($endOfYear) {
                        $sub->whereNull('fecha_inicio')
                            ->orWhere('fecha_inicio', '<=', $endOfYear);
                    })->where(function($sub) use ($startOfYear) {
                        $sub->whereNull('fecha_fin')
                            ->orWhere('fecha_fin', '>=', $startOfYear);
                    });
                })
                ->when($clientId, function ($query, $clientId) {
                    $query->where('client_id', $clientId);
                })->get();
        } else {
            $mantenimientosContratos = collect();
        }

        // Combinar y preparar iteración de meses
        $todos = $serviciosProyecto->concat($serviciosMantenimiento);
        
        $maxMonth = 12; // Siempre mostrar los 12 meses para proyecciones y consultar el histórico completo

        $resumenMensual = collect();

        for ($m = 1; $m <= $maxMonth; $m++) {
            $mesStr = sprintf('%02d', $m); 
            $fechaMes = \Carbon\Carbon::createFromDate($year, $m, 1);
            $nombreMes = ucfirst($fechaMes->translatedFormat('F'));

            $itemsDelMes = $todos->filter(function ($item) use ($mesStr) {
                return $item['mes'] === $mesStr;
            });

            // Ingresos Fijos del Proyecto (ya prefiltrados a 1 por año en el map inicial)
            $ingresosProyectosMensual = $itemsDelMes->where('tipo', 'Proyecto')->sum('ingreso_fijo');
            
            // Ingresos de Mantenimiento iterativos por mes natural (1 mensualidad por cliente/mes sin importar horas)
            $ingresosMantenimientosMensual = $mantenimientosContratos->sum(function ($mant) use ($m, $year) {
                return (float) $mant->calculatePeriodIncome($m, $year);
            });

            // Añadir el mes a la tabla solo si hay ingresos pasivos fijos o si el equipo ha registrado minutos
            if ($itemsDelMes->count() > 0 || $ingresosMantenimientosMensual > 0 || $ingresosProyectosMensual > 0) {
                $resumenMensual->push([
                    'mes' => $mesStr,
                    'nombre' => $nombreMes,
                    'total_minutos' => $itemsDelMes->sum('minutos'),
                    'total_horas' => round($itemsDelMes->sum('minutos') / 60, 2),
                    'total_importe_horas' => round($itemsDelMes->sum('importe_horas'), 2), // Costo x Horas
                    'total_facturado_proyectos' => round($ingresosProyectosMensual, 2),
                    'total_facturado_mantenimientos' => round($ingresosMantenimientosMensual, 2),
                    'total_facturado' => round($ingresosProyectosMensual + $ingresosMantenimientosMensual, 2),
                    'detalle' => $itemsDelMes->sortByDesc('fecha')->values(),
                ]);
            }
        }
        
        $resumenMensual = $resumenMensual->sortBy('mes')->values();

        // Estadísticas anuales
        $totalFacturadoAnual = $resumenMensual->sum('total_facturado');
        $totalProyectosAnual = $resumenMensual->sum('total_facturado_proyectos');
        $totalMantenimientosAnual = $resumenMensual->sum('total_facturado_mantenimientos');

        // Estadísticas (según los datos filtrados)
        $stats = [
            'total_horas' => round($todos->sum('minutos') / 60, 2),
            'total_importe_horas' => round($todos->sum('importe_horas'), 2),
            'total_proyectos' => round($totalProyectosAnual, 2),
            'total_mantenimientos' => round($totalMantenimientosAnual, 2),
            'total_facturado' => round($totalFacturadoAnual, 2),
            'promedio_mensual_facturado' => $resumenMensual->count() > 0 ? round($totalFacturadoAnual / $resumenMensual->count(), 2) : 0,
        ];
        
        $clientes = \App\Models\Client::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Horas/Index', [
            'resumenMensual' => $resumenMensual,
            'stats' => $stats,
            'filters' => $filters,
            'clientes' => $clientes,
        ]);
    }
}
