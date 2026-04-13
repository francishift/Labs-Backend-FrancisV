<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use App\Models\Client;
use App\Models\Extension;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\MantenimientoPdfMail;

class MantenimientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $mantenimientos = Mantenimiento::query()
            ->select(['id', 'aplicacion', 'descripcion', 'url', 'fecha_inicio', 'tipo_pago', 'importe', 'estado', 'client_id'])
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

        // Métrica global solicitada: Gasto Anual Soft/Host (Suma de costo anual de software activo + coste anual extensiones activas)
        $extensionesAnuales = \App\Models\Extension::where('estado', 'Activada')->get()->sum(fn($e) => $e->calculatePeriodCost('all'));
        $softwareAnual = \App\Models\Software::getTotalAnual();

        return Inertia::render('Admin/Mantenimientos/Index', [
            'mantenimientos' => $mantenimientos,
            'filters' => $request->only(['search']),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'availableExtensions' => Extension::orderBy('nombre')->get(['id', 'nombre', 'precio']),
            'stats' => array_merge(Mantenimiento::getAggregatedStatsForYear(), [
                'gastos_anuales_soft_host' => $softwareAnual + $extensionesAnuales
            ]),
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

        $data['precio_hora'] = Mantenimiento::getDiscountedHourlyRate();
        $data['porcentaje_software'] = (float) \App\Models\Configuracion::get('porcentaje_software', 2);
        $data['coste_software_anual'] = \App\Models\Software::getTotalAnual();
        
        $mantenimiento = Mantenimiento::create($data);
        
        if ($request->has('extensiones')) {
            $mantenimiento->syncExtensionSnapshots($request->extensiones);
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
            'extensiones:id,nombre,precio,tipo_licencia',
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
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'servicios_page')
            ->withQueryString();
            
        $financialStats = $mantenimiento->getFinancialStats($month, $year);

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
            'stats' => array_merge($financialStats, [
                'periodo' => [
                    'month' => $month === 'all' ? 'all' : (int)$month,
                    'year' => (int)$year
                ]
            ]),
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
            $mantenimiento->syncExtensionSnapshots($request->extensiones);
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

    public function exportPdf(Request $request, Mantenimiento $mantenimiento)
    {
        $year = (int) $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));
        if ($month !== 'all') $month = (int) $month;

        $mantenimiento->load([
            'cliente:id,name,email,phone,mobile',
            'extensiones:id,nombre,precio,tipo_licencia',
            'servicios' => function($q) use ($year, $month) {
                $q->when($year, function ($query) use ($year) {
                    $query->whereYear('fecha', $year);
                })
                ->when($month !== 'all', function ($query) use ($month) {
                    $query->whereMonth('fecha', $month);
                })
                ->orderBy('fecha', 'desc')
                ->orderBy('created_at', 'desc');
            },
        ]);

        $stats = $mantenimiento->getFinancialStats($month, $year);

        $logoPath = public_path('img/logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $hidePrices = $request->boolean('hide_prices', false);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.mantenimiento', [
            'mantenimiento' => $mantenimiento,
            'logoBase64' => $logoBase64,
            'stats' => $stats,
            'periodo' => [
                'month' => $month,
                'year' => $year
            ],
            'precioHoraFallback' => $mantenimiento->precio_hora ?: \App\Models\Mantenimiento::getDiscountedHourlyRate(),
            'hidePrices' => $hidePrices,
        ]);

        if ($request->has('download')) {
            return $pdf->download("Mantenimiento-{$mantenimiento->id}.pdf");
        }

        return $pdf->stream("Mantenimiento-{$mantenimiento->id}.pdf");
    }

    public function sendPdfEmail(Request $request, Mantenimiento $mantenimiento)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $year = (int) $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));
        if ($month !== 'all') $month = (int) $month;

        $mantenimiento->load([
            'cliente:id,name,email,phone,mobile',
            'extensiones:id,nombre,precio,tipo_licencia',
            'servicios' => function($q) use ($year, $month) {
                $q->when($year, function ($query) use ($year) {
                    $query->whereYear('fecha', $year);
                })
                ->when($month !== 'all', function ($query) use ($month) {
                    $query->whereMonth('fecha', $month);
                })
                ->orderBy('fecha', 'desc')
                ->orderBy('created_at', 'desc');
            },
        ]);

        $stats = $mantenimiento->getFinancialStats($month, $year);

        $logoPath = public_path('img/logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $hidePrices = $request->boolean('hide_prices', false);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.mantenimiento', [
            'mantenimiento' => $mantenimiento,
            'logoBase64' => $logoBase64,
            'stats' => $stats,
            'periodo' => [
                'month' => $month,
                'year' => $year
            ],
            'precioHoraFallback' => $mantenimiento->precio_hora ?: \App\Models\Mantenimiento::getDiscountedHourlyRate(),
            'hidePrices' => $hidePrices,
        ]);

        $pdfOutput = $pdf->output();

        Mail::to($request->email)->send(new MantenimientoPdfMail($mantenimiento, $pdfOutput, $month, $year));

        return back()->with('success', 'Email enviado correctamente.');
    }
}
