<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Servicio;
use App\Models\Proyecto;
use App\Models\Configuracion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'proyecto_id' => 'nullable|integer|exists:proyectos,id',
        ]);

        $servicios = Servicio::query()
            ->select(['id', 'servicio', 'descripcion', 'fecha', 'duracion_minutos', 'precio', 'proyecto_id'])
            ->with('proyecto:id,proyecto')
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('servicio', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%")
                      ->orWhereHas('proyecto', function($q) use ($search) {
                          $q->where('proyecto', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->input('proyecto_id'), function ($query, $proyectoId) {
                $query->where('proyecto_id', $proyectoId);
            })
            ->orderBy('fecha', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Servicios/Index', [
            'servicios' => $servicios,
            'filters' => $request->only(['search', 'proyecto_id']),
            'proyectos' => Proyecto::orderBy('proyecto')->get(['id', 'proyecto']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'servicio' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'proyecto_id' => 'required|exists:proyectos,id',
            'fecha' => 'required|date',
            'duracion_minutos' => 'required|integer|min:1',
            'precio' => 'nullable|numeric|min:0',
        ]);

        $proyecto = Proyecto::find($data['proyecto_id']);
        $data['precio_hora'] = $proyecto->precio_hora ?: Configuracion::get('precio_hora', 0);

        Servicio::create($data);

        return back()
            ->with('success', 'Servicio creado correctamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Servicio $servicio)
    {
        $data = $request->validate([
            'servicio' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'proyecto_id' => 'required|exists:proyectos,id',
            'fecha' => 'required|date',
            'duracion_minutos' => 'required|integer|min:1',
            'precio' => 'nullable|numeric|min:0',
        ]);

        $servicio->update($data);

        return back()
            ->with('success', 'Servicio actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Servicio $servicio)
    {
        $servicio->delete();

        return back()
            ->with('success', 'Servicio eliminado correctamente.');
    }
}
