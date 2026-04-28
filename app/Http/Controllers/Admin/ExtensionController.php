<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\StoreExtensionRequest;
use App\Http\Requests\UpdateExtensionRequest;

class ExtensionController extends Controller
{
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

    public function store(StoreExtensionRequest $request)
    {
        Extension::create($request->validated());
        return back()->with('success', 'Extensión creada correctamente.');
    }

    public function update(UpdateExtensionRequest $request, Extension $extensione)
    {
        $oldPrecio = $extensione->precio;
        $extensione->update($request->validated());

        if ($oldPrecio != $extensione->precio) {
            app(\App\Services\ExtensionPricingService::class)->recalculateForExtension($extensione);
        }

        return back()->with('success', 'Extensión actualizada correctamente.');
    }

    public function destroy(Extension $extensione)
    {
        $extensione->delete();
        
        app(\App\Services\ExtensionPricingService::class)->recalculateForExtension($extensione);

        return back()->with('success', 'Extensión eliminada correctamente.');
    }
}
