<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MantenimientoServicio;
use App\Models\Mantenimiento;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\StoreMantenimientoServicioRequest;
use App\Http\Requests\UpdateMantenimientoServicioRequest;

class MantenimientoServicioController extends Controller
{
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
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/MantenimientoServicios/Index', [
            'servicios' => $servicios,
            'filters' => $request->only(['search']),
            'mantenimientos' => Mantenimiento::orderBy('aplicacion')->get(['id', 'aplicacion']),
        ]);
    }

    public function store(StoreMantenimientoServicioRequest $request)
    {
        $data = $request->validated();
        $data['precio_hora'] = Mantenimiento::getDiscountedHourlyRate();

        MantenimientoServicio::create($data);

        return back()->with('success', 'Servicio de mantenimiento registrado correctamente.');
    }

    public function update(UpdateMantenimientoServicioRequest $request, MantenimientoServicio $mantenimientoServicio)
    {
        $mantenimientoServicio->update($request->validated());

        return back()->with('success', 'Servicio de mantenimiento actualizado correctamente.');
    }

    public function destroy(MantenimientoServicio $mantenimientoServicio)
    {
        $mantenimientoServicio->delete();

        return back()->with('success', 'Servicio de mantenimiento eliminado correctamente.');
    }
}
