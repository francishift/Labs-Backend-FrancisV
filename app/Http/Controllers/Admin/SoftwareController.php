<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Software;
use App\Models\Proyecto;
use App\Models\Mantenimiento;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SoftwareController extends Controller
{
    public function index(Request $request)
    {
        $softwares = Software::query()
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%")
                      ->orWhere('tipo', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        $stats = Software::getAggregatedYearlyStats();

        return Inertia::render('Admin/Software/Index', [
            'softwares' => $softwares,
            'stats' => $stats,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo' => 'required|in:Software,Hosting',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo_licencia' => 'required|in:Anual,Mensual',
            'precio' => 'required|numeric|min:0',
            'estado' => 'required|in:Activa,Finalizada',
        ]);

        Software::create($data);

        return back()->with('success', 'Elemento creado correctamente.');
    }

    public function update(Request $request, Software $software)
    {
        $data = $request->validate([
            'tipo' => 'required|in:Software,Hosting',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo_licencia' => 'required|in:Anual,Mensual',
            'precio' => 'required|numeric|min:0',
            'estado' => 'required|in:Activa,Finalizada',
        ]);

        $software->update($data);

        return back()->with('success', 'Elemento actualizado correctamente.');
    }

    public function destroy(Software $software)
    {
        $software->delete();

        return back()->with('success', 'Elemento eliminado correctamente.');
    }
}
