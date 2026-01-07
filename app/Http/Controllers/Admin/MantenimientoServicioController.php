<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MantenimientoServicio;
use App\Models\Mantenimiento;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MantenimientoServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $servicios = MantenimientoServicio::query()
            ->select(['id', 'mantenimiento_id', 'descripcion', 'duracion_minutos', 'fecha'])
            ->with(['mantenimiento:id,aplicacion,client_id', 'mantenimiento.cliente:id,name'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('descripcion', 'like', "%{$search}%")
                      ->orWhereHas('mantenimiento', function($q) use ($search) {
                          $q->where('aplicacion', 'like', "%{$search}%");
                      });
                });
            })
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/MantenimientoServicios/Index', [
            'servicios' => $servicios,
            'filters' => $request->only(['search']),
            'mantenimientos' => Mantenimiento::orderBy('aplicacion')->get(['id', 'aplicacion']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'mantenimiento_id' => 'required|exists:mantenimientos,id',
            'descripcion' => 'required|string',
            'duracion_minutos' => 'required|integer|min:1',
            'fecha' => 'required|date',
        ]);

        $mantenimiento = Mantenimiento::find($data['mantenimiento_id']);
        $data['precio_hora'] = $mantenimiento->precio_hora ?: Mantenimiento::getDiscountedHourlyRate();

        MantenimientoServicio::create($data);

        return back()->with('success', 'Servicio de mantenimiento registrado correctamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MantenimientoServicio $mantenimientoServicio)
    {
        $data = $request->validate([
            'descripcion' => 'required|string',
            'duracion_minutos' => 'required|integer|min:1',
            'fecha' => 'required|date',
        ]);

        $mantenimientoServicio->update($data);

        return back()->with('success', 'Servicio de mantenimiento actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MantenimientoServicio $mantenimientoServicio)
    {
        $mantenimientoServicio->delete();

        return back()->with('success', 'Servicio de mantenimiento eliminado correctamente.');
    }
}
