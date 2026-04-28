<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use App\Models\Client;
use App\Models\Extension;
use App\Models\Software;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\StoreMantenimientoRequest;
use App\Http\Requests\UpdateMantenimientoRequest;
use App\Services\MantenimientoService;
use App\Services\DocumentPdfService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class MantenimientoController extends Controller
{
    private MantenimientoService $mantenimientoService;
    private DocumentPdfService $pdfService;

    public function __construct(MantenimientoService $mantenimientoService, DocumentPdfService $pdfService)
    {
        $this->mantenimientoService = $mantenimientoService;
        $this->pdfService = $pdfService;
    }

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

        $extensionesAnuales = Extension::where('estado', 'Activada')->get()->sum(fn($e) => $e->calculatePeriodCost('all'));
        $softwareAnual = Software::getTotalAnual();

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

    public function store(StoreMantenimientoRequest $request)
    {
        $this->mantenimientoService->crearMantenimiento(
            $request->validated(),
            $request->input('extensiones')
        );

        return back()->with('success', 'Mantenimiento creado correctamente.');
    }

    public function show(Request $request, Mantenimiento $mantenimiento)
    {
        $mantenimiento->load([
            'cliente:id,name,email,phone,mobile',
            'extensiones:id,nombre,precio,tipo_licencia',
        ]);
        
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));
        
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

        $aniosDisponibles = $mantenimiento->servicios()->selectRaw('YEAR(fecha) as year')->distinct()->pluck('year')->toArray();
        if (!in_array(date('Y'), $aniosDisponibles)) $aniosDisponibles[] = (int)date('Y');
        sort($aniosDisponibles);

        $allMantenimientoIds = Mantenimiento::query()
            ->orderBy('fecha_inicio', 'desc')
            ->orderBy('id', 'desc')
            ->pluck('id')
            ->toArray();

        $currentIndex = array_search($mantenimiento->id, $allMantenimientoIds);
        $currentPage = $currentIndex !== false ? $currentIndex + 1 : 1;

        $paginator = new LengthAwarePaginator(
            [$mantenimiento],
            count($allMantenimientoIds),
            1,
            $currentPage,
            ['path' => route('admin.mantenimientos.index')]
        );

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
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'availableExtensions' => Extension::orderBy('nombre')->get(['id', 'nombre']),
            'stats' => array_merge($financialStats, [
                'periodo' => [
                    'month' => $month === 'all' ? 'all' : (int)$month,
                    'year' => (int)$year
                ]
            ]),
            'aniosDisponibles' => array_reverse($aniosDisponibles),
        ]);
    }

    public function update(UpdateMantenimientoRequest $request, Mantenimiento $mantenimiento)
    {
        $this->mantenimientoService->actualizarMantenimiento(
            $mantenimiento,
            $request->validated(),
            $request->input('extensiones')
        );

        return back()->with('success', 'Mantenimiento actualizado correctamente.');
    }

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

        $hidePrices = $request->boolean('hide_prices', false);
        
        $pdf = $this->pdfService->generateMantenimientoPdf($mantenimiento, $month, $year, $hidePrices);

        $monthName = $month === 'all' ? 'Anual' : ucfirst(Carbon::create()->month($month)->locale('es')->monthName);
        $fileName = "Mantenimiento-{$monthName}-{$year}.pdf";

        if ($request->has('download')) {
            return $pdf->download($fileName);
        }

        return $pdf->stream($fileName);
    }

    public function sendPdfEmail(Request $request, Mantenimiento $mantenimiento)
    {
        $request->validate(['email' => 'required|email']);

        $year = (int) $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));
        if ($month !== 'all') $month = (int) $month;

        $hidePrices = $request->boolean('hide_prices', false);

        $this->mantenimientoService->enviarPdfPorEmail(
            $mantenimiento,
            $request->email,
            $month,
            $year,
            $hidePrices
        );

        return back()->with('success', 'Email enviado correctamente.');
    }
}
