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
    public function index(Request $request)
    {
        $extensiones = Extension::query()
            ->select(['id', 'nombre', 'descripcion', 'url', 'precio', 'tipo_licencia'])
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'url' => 'nullable|url|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'tipo_licencia' => 'required|in:Anual,Mensual,Pago único',
        ]);

        Extension::create($data);

        return back()->with('success', 'Extensión creada correctamente.');
    }

    public function update(Request $request, Extension $extensione)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'url' => 'nullable|url|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
        ]);

        $extensione->update($data);

        return back()->with('success', 'Extensión actualizada correctamente.');
    }

    public function destroy(Extension $extensione)
    {
        $extensione->delete();

        return back()->with('success', 'Extensión eliminada correctamente.');
    }
}
