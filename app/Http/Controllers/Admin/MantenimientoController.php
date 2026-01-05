<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use App\Models\Client;
use App\Models\Extension;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MantenimientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $mantenimientos = Mantenimiento::query()
            ->select(['id', 'aplicacion', 'url', 'fecha_inicio', 'tipo_pago', 'importe', 'estado', 'client_id'])
            ->with(['cliente:id,name', 'extensiones:id,nombre'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('aplicacion', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%")
                      ->orWhereHas('cliente', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->orderBy('fecha_inicio', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Mantenimientos/Index', [
            'mantenimientos' => $mantenimientos,
            'filters' => $request->only(['search']),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'availableExtensions' => Extension::orderBy('nombre')->get(['id', 'nombre', 'precio']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'aplicacion' => 'required|string|max:255',
            'url' => 'nullable|url|max:255',
            'client_id' => 'required|exists:clients,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'tipo_pago' => 'required|string|in:mensual,trimestral,anual',
            'importe' => 'required|numeric|min:0',
            'estado' => 'required|string|in:en curso,finalizado',
            'descripcion' => 'nullable|string',
            'extensiones' => 'nullable|array',
            'extensiones.*' => 'exists:extensiones,id',
        ]);

        $mantenimiento = Mantenimiento::create($data);

        if ($request->has('extensiones')) {
            $mantenimiento->extensiones()->sync($request->extensiones);
        }

        return back()->with('success', 'Mantenimiento creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Mantenimiento $mantenimiento)
    {
        $mantenimiento->load([
            'cliente:id,name,email,phone,mobile',
            'extensiones:id,nombre,precio,tipo_licencia'
        ]);
        
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('n')); // 1-12 o 'all'
        
        // Filtro de servicios por periodo
        $serviciosQuery = $mantenimiento->servicios()
            ->when($year, function ($q) use ($year) {
                $q->whereYear('fecha', $year);
            })
            ->when($month !== 'all', function ($q) use ($month) {
                $q->whereMonth('fecha', $month);
            });

        $servicios = (clone $serviciosQuery)
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'servicios_page')
            ->withQueryString();

        // Cálculos de costes de servicios en el periodo
        $precioHoraConDescuento = Mantenimiento::getDiscountedHourlyRate();
        $totalMinutosPeriodo = $serviciosQuery->sum('duracion_minutos');
        $totalCosteServiciosPeriodo = ($totalMinutosPeriodo / 60) * $precioHoraConDescuento;

        // Cálculo de ingresos proporcionales
        $ingresoPeriodo = $mantenimiento->calculatePeriodIncome($month);
        $costeExtensionesPeriodo = $mantenimiento->extensiones->sum(fn($ext) => $ext->calculatePeriodCost($month));

        // Datos para los selectores de filtro
        $aniosDisponibles = $mantenimiento->servicios()->selectRaw('YEAR(fecha) as year')->distinct()->pluck('year')->toArray();
        if (!in_array(date('Y'), $aniosDisponibles)) $aniosDisponibles[] = (int)date('Y');
        sort($aniosDisponibles);

        // Obtenemos todos los IDs en el orden correcto para encontrar la "página" del mantenimiento actual
        $allMantenimientoIds = Mantenimiento::query()
            ->orderBy('fecha_inicio', 'desc')
            ->orderBy('id', 'desc')
            ->pluck('id')
            ->toArray();

        $currentIndex = array_search($mantenimiento->id, $allMantenimientoIds);
        $currentPage = $currentIndex !== false ? $currentIndex + 1 : 1;

        // Creamos un paginador manual para la navegación entre mantenimientos
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            [$mantenimiento],
            count($allMantenimientoIds),
            1,
            $currentPage,
            ['path' => route('admin.mantenimientos.index')]
        );

        // Ajustamos los links para que apunten à 'show'
        $paginationData = $paginator->toArray();
        foreach ($paginationData['links'] as &$link) {
            if ($link['url']) {
                $urlParts = parse_url($link['url'], PHP_URL_QUERY);
                if ($urlParts) {
                    parse_str($urlParts, $query);
                    if (isset($query['page'])) {
                        $targetPageIndex = (int)$query['page'] - 1;
                        if (isset($allMantenimientoIds[$targetPageIndex])) {
                            $targetId = $allMantenimientoIds[$targetPageIndex];
                            $link['url'] = route('admin.mantenimientos.show', $targetId);
                        }
                    }
                }
            }
        }

        return Inertia::render('Admin/Mantenimientos/Show', [
            'mantenimiento' => $mantenimiento,
            'servicios' => $servicios,
            'pagination' => $paginationData,
            'clients' => \App\Models\Client::orderBy('name')->get(['id', 'name']),
            'availableExtensions' => \App\Models\Extension::orderBy('nombre')->get(['id', 'nombre']),
            'stats' => [
                'ingreso' => $ingresoPeriodo,
                'coste_servicios' => $totalCosteServiciosPeriodo,
                'coste_extensiones' => $costeExtensionesPeriodo,
                'balance' => $ingresoPeriodo - $totalCosteServiciosPeriodo - $costeExtensionesPeriodo,
                'periodo' => [
                    'month' => $month === 'all' ? 'all' : (int)$month,
                    'year' => (int)$year
                ]
            ],
            'aniosDisponibles' => array_reverse($aniosDisponibles),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Mantenimiento $mantenimiento)
    {
        $data = $request->validate([
            'aplicacion' => 'required|string|max:255',
            'url' => 'nullable|url|max:255',
            'client_id' => 'required|exists:clients,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'tipo_pago' => 'required|string|in:mensual,trimestral,anual',
            'importe' => 'required|numeric|min:0',
            'estado' => 'required|string|in:en curso,finalizado',
            'descripcion' => 'nullable|string',
            'extensiones' => 'nullable|array',
            'extensiones.*' => 'exists:extensiones,id',
        ]);

        $mantenimiento->update($data);

        if ($request->has('extensiones')) {
            $mantenimiento->extensiones()->sync($request->extensiones);
        }

        return back()->with('success', 'Mantenimiento actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mantenimiento $mantenimiento)
    {
        $mantenimiento->delete();

        return back()->with('success', 'Mantenimiento eliminado correctamente.');
    }
}
