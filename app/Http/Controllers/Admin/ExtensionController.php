<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Models\Proyecto;
use App\Models\Mantenimiento;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExtensionController extends Controller
{
    /**
     * Muestra el listado paginado de extensiones.
     * Incluye buscador global y estadísticas agregadas.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $extensiones = Extension::query()
            ->select(['id', 'nombre', 'descripcion', 'url', 'precio', 'tipo_licencia', 'estado'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%")
                      ->orWhere('url', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        $stats = Extension::getAggregatedYearlyStats();

        return Inertia::render('Admin/Extensiones/Index', [
            'extensiones' => $extensiones,
            'stats' => $stats,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Almacena una nueva extensión en la base de datos.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'url' => 'nullable|url|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'tipo_licencia' => 'required|in:Anual,Mensual,Pago único',
            'estado' => 'required|in:Activada,Cancelada',
        ]);

        Extension::create($data);

        return back()->with('success', 'Extensión creada correctamente.');
    }

    /**
     * Actualiza los datos de una extensión existente.
     *
     * @param Request $request
     * @param Extension $extensione
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Extension $extensione)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'url' => 'nullable|url|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'tipo_licencia' => 'required|in:Anual,Mensual,Pago único',
            'estado' => 'required|in:Activada,Cancelada',
        ]);

        $oldPrecio = $extensione->precio;
        $extensione->update($data);

        // Si el precio ha cambiado, forzar recálculo masivo para proyectos activos
        if ($oldPrecio != $extensione->precio) {
            app(\App\Services\ExtensionPricingService::class)->recalculateForExtension($extensione);
        }

        return back()->with('success', 'Extensión actualizada correctamente.');
    }

    /**
     * Elimina una extensión específica (Soft Delete).
     *
     * @param Extension $extensione
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Extension $extensione)
    {
        $extensione->delete();
        
        // Recalcular sus precios para los proyectos activos tras eliminarse (soft-delete)
        app(\App\Services\ExtensionPricingService::class)->recalculateForExtension($extensione);

        return back()->with('success', 'Extensión eliminada correctamente.');
    }
}
