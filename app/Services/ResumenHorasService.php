<?php

namespace App\Services;

use App\Models\Servicio;
use App\Models\MantenimientoServicio;
use App\Models\Mantenimiento;
use App\Models\Configuracion;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ResumenHorasService
{
    /**
     * Calcula los totales anuales y mensuales (Sin detalle de registros).
     *
     * @param int $year
     * @param int|null $clientId
     * @param string|null $tipoServicio
     * @return array{resumenMensual: \Illuminate\Support\Collection, stats: array<string, mixed>}
     */
    public function getResumenAnual(int $year, ?int $clientId, ?string $tipoServicio): array
    {
        $basePrecioHora = (float) Configuracion::get('precio_hora', 0);
        $mantenimientoPrecioHora = (float) Mantenimiento::getDiscountedHourlyRate();
        
        $serviciosProyecto = collect();
        $serviciosMantenimiento = collect();
        $mantenimientosContratos = collect();

        if (!$tipoServicio || $tipoServicio === 'proyectos') {
            $serviciosProyecto = $this->getServiciosProyecto($year, $clientId, $basePrecioHora);
        }

        if (!$tipoServicio || $tipoServicio === 'mantenimientos') {
            $serviciosMantenimiento = $this->getServiciosMantenimiento($year, $clientId, $mantenimientoPrecioHora);
            $mantenimientosContratos = $this->getMantenimientosContratos($year, $clientId);
        }

        $todos = $serviciosProyecto->concat($serviciosMantenimiento);
        $resumenMensual = $this->agruparPorMeses($year, $todos, $mantenimientosContratos);

        $totalFacturadoAnual = $resumenMensual->sum('total_facturado');
        
        $stats = [
            'total_horas' => round($todos->sum('minutos') / 60, 2),
            'total_importe_horas' => round($todos->sum('importe_horas'), 2),
            'total_proyectos' => round($resumenMensual->sum('total_facturado_proyectos'), 2),
            'total_mantenimientos' => round($resumenMensual->sum('total_facturado_mantenimientos'), 2),
            'total_facturado' => round($totalFacturadoAnual, 2),
            'promedio_mensual_facturado' => $resumenMensual->count() > 0 ? round($totalFacturadoAnual / $resumenMensual->count(), 2) : 0,
        ];

        return [
            'resumenMensual' => $resumenMensual,
            'stats' => $stats
        ];
    }

    /**
     * Obtiene y calcula los servicios de proyectos del año.
     */
    private function getServiciosProyecto(int $year, ?int $clientId, float $basePrecioHora): Collection
    {
        $serviciosDb = Servicio::with('proyecto:id,client_id,precio_hora,presupuesto')
            ->whereYear('fecha', $year)
            ->when($clientId, function ($query, $clientId) {
                $query->whereHas('proyecto', function ($q) use ($clientId) {
                    $q->where('client_id', $clientId);
                });
            })
            ->get(['id', 'proyecto_id', 'fecha', 'duracion_minutos', 'precio_hora']);
            
        $proyectosVistos = [];
        
        return $serviciosDb->map(function ($s) use ($basePrecioHora, &$proyectosVistos) {
            $precioHora = $s->precio_hora ?: ($s->proyecto->precio_hora ?: $basePrecioHora);
            
            $esUltimoServicioDelAno = !in_array($s->proyecto_id, $proyectosVistos);
            if ($esUltimoServicioDelAno) {
                $proyectosVistos[] = $s->proyecto_id;
            }

            return [
                'mes' => $s->fecha->format('m'),
                'tipo' => 'Proyecto',
                'minutos' => $s->duracion_minutos,
                'importe_horas' => ($s->duracion_minutos / 60) * $precioHora,
                'ingreso_fijo' => $esUltimoServicioDelAno ? (float) ($s->proyecto->presupuesto ?? 0) : 0 
            ];
        });
    }

    /**
     * Obtiene y calcula los servicios de mantenimientos del año.
     */
    private function getServiciosMantenimiento(int $year, ?int $clientId, float $mantenimientoPrecioHora): Collection
    {
        return MantenimientoServicio::with('mantenimiento:id,client_id,precio_hora')
            ->whereYear('fecha', $year)
            ->when($clientId, function ($query, $clientId) {
                $query->whereHas('mantenimiento', function ($q) use ($clientId) {
                    $q->where('client_id', $clientId);
                });
            })
            ->get(['id', 'mantenimiento_id', 'fecha', 'duracion_minutos', 'precio_hora'])
            ->map(function ($s) use ($mantenimientoPrecioHora) {
                $precioHora = $s->precio_hora ?: ($s->mantenimiento->precio_hora ?: $mantenimientoPrecioHora);
                
                return [
                    'mes' => $s->fecha->format('m'),
                    'tipo' => 'Mantenimiento',
                    'minutos' => $s->duracion_minutos,
                    'importe_horas' => ($s->duracion_minutos / 60) * $precioHora,
                ];
            });
    }

    /**
     * Obtiene los contratos de mantenimientos activos en el año.
     */
    private function getMantenimientosContratos(int $year, ?int $clientId): Collection
    {
        return Mantenimiento::with('precios')
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
    }

    /**
     * Agrupa los servicios y mantenimientos por meses.
     */
    private function agruparPorMeses(int $year, Collection $todos, Collection $mantenimientosContratos): Collection
    {
        $resumenMensual = collect();

        for ($m = 1; $m <= 12; $m++) {
            $mesStr = sprintf('%02d', $m); 
            $fechaMes = Carbon::createFromDate($year, $m, 1);
            $nombreMes = ucfirst($fechaMes->translatedFormat('F'));

            $itemsDelMes = $todos->filter(function ($item) use ($mesStr) {
                return $item['mes'] === $mesStr;
            });

            $ingresosProyectosMensual = $itemsDelMes->where('tipo', 'Proyecto')->sum('ingreso_fijo');
            
            $ingresosMantenimientosMensual = $mantenimientosContratos->sum(function ($mant) use ($m, $year) {
                return (float) $mant->calculatePeriodIncome($m, $year);
            });

            if ($itemsDelMes->count() > 0 || $ingresosMantenimientosMensual > 0 || $ingresosProyectosMensual > 0) {
                $resumenMensual->push([
                    'mes' => $mesStr,
                    'nombre' => $nombreMes,
                    'total_minutos' => $itemsDelMes->sum('minutos'),
                    'total_horas' => round($itemsDelMes->sum('minutos') / 60, 2),
                    'total_importe_horas' => round($itemsDelMes->sum('importe_horas'), 2),
                    'total_facturado_proyectos' => round($ingresosProyectosMensual, 2),
                    'total_facturado_mantenimientos' => round($ingresosMantenimientosMensual, 2),
                    'total_facturado' => round($ingresosProyectosMensual + $ingresosMantenimientosMensual, 2),
                    'detalle' => [], // El detalle se cargará vía Lazy Loading
                    'cargando_detalle' => false,
                    'detalle_cargado' => false,
                ]);
            }
        }
        
        return $resumenMensual->sortBy('mes')->values();
    }

    /**
     * Obtiene el detalle (registros) de un mes en particular para el Lazy Loading.
     */
    public function getDetalleMes(int $year, int $month, ?int $clientId, ?string $tipoServicio): Collection
    {
        $basePrecioHora = Configuracion::get('precio_hora', 0);
        $mantenimientoPrecioHora = Mantenimiento::getDiscountedHourlyRate();
        
        $detalle = collect();

        // Proyectos
        if (!$tipoServicio || $tipoServicio === 'proyectos') {
            $servicios = Servicio::with('proyecto.client')
                ->whereYear('fecha', $year)
                ->whereMonth('fecha', $month)
                ->when($clientId, function ($query, $clientId) {
                    $query->whereHas('proyecto', function ($q) use ($clientId) {
                        $q->where('client_id', $clientId);
                    });
                })
                ->orderBy('fecha', 'desc')
                ->get();
                
            foreach ($servicios as $s) {
                $precioHora = $s->precio_hora ?: ($s->proyecto->precio_hora ?: $basePrecioHora);
                $detalle->push([
                    'id' => 'p_' . $s->id,
                    'fecha' => $s->fecha->format('Y-m-d'),
                    'tipo' => 'Proyecto',
                    'nombre' => $s->proyecto->proyecto ?? 'N/A',
                    'cliente' => $s->proyecto->client->name ?? 'N/A',
                    'descripcion' => $s->servicio,
                    'minutos' => $s->duracion_minutos,
                    'importe_horas' => ($s->duracion_minutos / 60) * $precioHora,
                ]);
            }
        }

        // Mantenimientos
        if (!$tipoServicio || $tipoServicio === 'mantenimientos') {
            $mantenimientos = MantenimientoServicio::with(['mantenimiento.cliente'])
                ->whereYear('fecha', $year)
                ->whereMonth('fecha', $month)
                ->when($clientId, function ($query, $clientId) {
                    $query->whereHas('mantenimiento', function ($q) use ($clientId) {
                        $q->where('client_id', $clientId);
                    });
                })
                ->orderBy('fecha', 'desc')
                ->get();
                
            foreach ($mantenimientos as $s) {
                $precioHora = $s->precio_hora ?: ($s->mantenimiento->precio_hora ?: $mantenimientoPrecioHora);
                $detalle->push([
                    'id' => 'm_' . $s->id,
                    'fecha' => $s->fecha->format('Y-m-d'),
                    'tipo' => 'Mantenimiento',
                    'nombre' => $s->mantenimiento->aplicacion ?? 'N/A',
                    'cliente' => $s->mantenimiento->cliente->name ?? 'N/A',
                    'descripcion' => $s->descripcion,
                    'minutos' => $s->duracion_minutos,
                    'importe_horas' => ($s->duracion_minutos / 60) * $precioHora,
                ]);
            }
        }

        return $detalle->sortByDesc('fecha')->values();
    }
}
