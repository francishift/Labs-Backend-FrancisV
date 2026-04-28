<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\Client;
use App\Models\Extension;
use App\Models\Software;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\StoreProyectoRequest;
use App\Http\Requests\UpdateProyectoRequest;
use App\Services\ProyectoService;
use App\Services\DocumentPdfService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class ProyectoController extends Controller
{
    private ProyectoService $proyectoService;
    private DocumentPdfService $pdfService;

    public function __construct(ProyectoService $proyectoService, DocumentPdfService $pdfService)
    {
        $this->proyectoService = $proyectoService;
        $this->pdfService = $pdfService;
    }

    public function index(Request $request)
    {
        $proyectos = Proyecto::query()
            ->select(['id', 'proyecto', 'descripcion', 'fecha_inicio', 'fecha_fin', 'presupuesto', 'estado', 'client_id', 'presupuesto_id'])
            ->with(['client:id,name', 'extensiones:id,nombre', 'facturas:id,proyecto_id'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('proyecto', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%")
                      ->orWhereHas('client', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->orderByRaw("CASE WHEN estado = 'En proceso' THEN 1 ELSE 2 END")
            ->orderBy('fecha_inicio', 'desc')
            ->paginate(10)
            ->withQueryString();

        $extensionesAnuales = Extension::where('estado', 'Activada')->get()->sum(fn($e) => $e->calculatePeriodCost('all'));
        $softwareAnual = Software::getTotalAnual();

        return Inertia::render('Admin/Proyectos/Index', [
            'proyectos' => $proyectos,
            'filters' => $request->only(['search']),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'availableExtensions' => Extension::orderBy('nombre')->get(['id', 'nombre', 'precio']),
            'stats' => array_merge(Proyecto::getAggregatedStatsForYear(), [
                'gastos_anuales_soft_host' => $softwareAnual + $extensionesAnuales
            ]),
        ]);
    }

    public function store(StoreProyectoRequest $request)
    {
        try {
            $this->proyectoService->crearProyecto(
                $request->validated(),
                $request->input('extensiones'),
                $request->input('facturas')
            );

            return back()->with('success', 'Proyecto creado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error creating project: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al crear el proyecto: ' . $e->getMessage()]);
        }
    }

    public function show(Proyecto $proyecto)
    {
        $proyecto->load([
            'client:id,name,email,phone,mobile',
            'presupuestoAsociado',
            'facturas',
            'servicios' => fn($q) => $q->orderBy('fecha', 'desc')->orderBy('created_at', 'desc'),
            'extensiones:id,nombre,precio,tipo_licencia',
        ]);

        $allProjectIds = Proyecto::query()
            ->orderBy('fecha_inicio', 'desc')
            ->orderBy('id', 'desc')
            ->pluck('id')
            ->toArray();

        $currentIndex = array_search($proyecto->id, $allProjectIds);
        $currentPage = $currentIndex !== false ? $currentIndex + 1 : 1;

        $paginator = new LengthAwarePaginator(
            [$proyecto],
            count($allProjectIds),
            1,
            $currentPage,
            ['path' => route('admin.proyectos.index')]
        );

        $paginationData = $paginator->toArray();
        foreach ($paginationData['links'] as &$link) {
            if ($link['url']) {
                $urlParts = parse_url($link['url'], PHP_URL_QUERY);
                if ($urlParts) {
                    parse_str($urlParts, $query);
                    if (isset($query['page'])) {
                        $targetPageIndex = (int)$query['page'] - 1;
                        if (isset($allProjectIds[$targetPageIndex])) {
                            $targetId = $allProjectIds[$targetPageIndex];
                            $link['url'] = route('admin.proyectos.show', $targetId);
                        }
                    }
                }
            }
        }

        return Inertia::render('Admin/Proyectos/Show', [
            'proyecto' => $proyecto,
            'pagination' => $paginationData,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'availableExtensions' => Extension::orderBy('nombre')->get(['id', 'nombre']),
            'stats' => $proyecto->getFinancialStats()
        ]);
    }

    public function update(UpdateProyectoRequest $request, Proyecto $proyecto)
    {
        try {
            $this->proyectoService->actualizarProyecto(
                $proyecto,
                $request->validated(),
                $request->input('extensiones'),
                $request->input('facturas')
            );

            return back()->with('success', 'Proyecto actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error updating project ' . $proyecto->id . ': ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al actualizar el proyecto: ' . $e->getMessage()]);
        }
    }

    public function destroy(Proyecto $proyecto)
    {
        $proyecto->delete();
        return back()->with('success', 'Proyecto eliminado correctamente.');
    }

    public function exportPdf(Request $request, Proyecto $proyecto)
    {
        $pdf = $this->pdfService->generateProyectoPdf($proyecto);

        if ($request->has('download')) {
            return $pdf->download("Proyecto-{$proyecto->id}.pdf");
        }

        return $pdf->stream("Proyecto-{$proyecto->id}.pdf");
    }

    public function sendPdfEmail(Request $request, Proyecto $proyecto)
    {
        $request->validate(['email' => 'required|email']);

        $this->proyectoService->enviarPdfPorEmail($proyecto, $request->email);

        return back()->with('success', 'Email enviado correctamente.');
    }
}
