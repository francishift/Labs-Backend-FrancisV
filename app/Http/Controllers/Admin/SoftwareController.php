<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Software;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\StoreSoftwareRequest;
use App\Http\Requests\UpdateSoftwareRequest;

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

    public function store(StoreSoftwareRequest $request)
    {
        Software::create($request->validated());
        return back()->with('success', 'Elemento creado correctamente.');
    }

    public function update(UpdateSoftwareRequest $request, Software $software)
    {
        $software->update($request->validated());
        return back()->with('success', 'Elemento actualizado correctamente.');
    }

    public function destroy(Software $software)
    {
        $software->delete();
        return back()->with('success', 'Elemento eliminado correctamente.');
    }
}
