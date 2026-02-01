<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\Client;
use App\Models\Extension;
use App\Models\Configuracion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProyectoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $proyectos = Proyecto::query()
            ->select(['id', 'proyecto', 'descripcion', 'fecha_inicio', 'presupuesto', 'estado', 'client_id'])
            ->with(['client:id,name', 'extensiones:id,nombre'])
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

        return Inertia::render('Admin/Proyectos/Index', [
            'proyectos' => $proyectos,
            'filters' => $request->only(['search']),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'availableExtensions' => Extension::orderBy('nombre')->get(['id', 'nombre', 'precio']),
            'stats' => Proyecto::getAggregatedStatsForYear(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'proyecto' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'presupuesto' => 'nullable|numeric|min:0',
            'estado' => 'required|string|in:En proceso,Finalizado,Cancelado',
            'client_id' => 'required|exists:clients,id',
            'extensiones' => 'nullable|array',
            'extensiones.*' => 'exists:extensiones,id',
        ]);

        $data['precio_hora'] = Configuracion::get('precio_hora', 0);
        $data['porcentaje_software'] = (float) Configuracion::get('porcentaje_software', 2);
        $data['coste_software_anual'] = \App\Models\Software::getTotalAnual();
        
        $proyecto = Proyecto::create($data);

        if ($request->has('extensiones')) {
            $proyecto->syncExtensionSnapshots($request->extensiones);
        }

        return back()
            ->with('success', 'Proyecto creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Proyecto $proyecto)
    {
        $proyecto->load([
            'client:id,name,email,phone,mobile',
            'servicios' => fn($q) => $q->orderBy('fecha', 'desc')->orderBy('created_at', 'desc'),
            'extensiones:id,nombre,precio,tipo_licencia',
        ]);

        // Obtenemos todos los IDs en el orden correcto para encontrar la "página" del proyecto actual
        $allProjectIds = Proyecto::query()
            ->orderBy('fecha_inicio', 'desc')
            ->orderBy('id', 'desc')
            ->pluck('id')
            ->toArray();

        $currentIndex = array_search($proyecto->id, $allProjectIds);
        $currentPage = $currentIndex !== false ? $currentIndex + 1 : 1;

        // Creamos un paginador manual para que el componente Pagination de Vue funcione exactamente igual que en el Index
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            [$proyecto], // El item actual
            count($allProjectIds), // Total de items
            1, // Items por página
            $currentPage, // Página actual
            ['path' => route('admin.proyectos.index')] // Usamos el index como base pero cambiamos el comportamiento en el componente
        );

        // Ajustamos los links para que apunten a 'show' en lugar de 'index'
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
            'clients' => \App\Models\Client::orderBy('name')->get(['id', 'name']),
            'availableExtensions' => \App\Models\Extension::orderBy('nombre')->get(['id', 'nombre']),
            'stats' => $proyecto->getFinancialStats()
        ]);
    }

    public function exportPdf(Request $request, Proyecto $proyecto)
    {
        $proyecto->load([
            'client',
            'servicios' => fn($q) => $q->orderBy('fecha', 'desc')->orderBy('created_at', 'desc'),
            'extensiones',
        ]);

        $stats = $proyecto->getFinancialStats();
        
        $logoPath = public_path('img/logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.proyecto', array_merge(
            compact('proyecto', 'logoBase64'),
            $stats
        ));
        
        if ($request->has('download')) {
            return $pdf->download("Proyecto-{$proyecto->id}.pdf");
        }

        return $pdf->stream("Proyecto-{$proyecto->id}.pdf");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Proyecto $proyecto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Proyecto $proyecto)
    {
        $data = $request->validate([
            'proyecto' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'presupuesto' => 'nullable|numeric|min:0',
            'estado' => 'required|string|in:En proceso,Finalizado,Cancelado',
            'client_id' => 'required|exists:clients,id',
            'extensiones' => 'nullable|array',
            'extensiones.*' => 'exists:extensiones,id',
        ]);

        $proyecto->update($data);

        if ($request->has('extensiones')) {
            $proyecto->syncExtensionSnapshots($request->extensiones);
        }

        return back()
            ->with('success', 'Proyecto actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Proyecto $proyecto)
    {
        $proyecto->delete();

        return back()
            ->with('success', 'Proyecto eliminado correctamente.');
    }
}
