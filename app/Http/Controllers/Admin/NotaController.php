<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Nota;
use Illuminate\Http\Request;
use App\Http\Requests\StoreNotaRequest;
use Inertia\Inertia;

class NotaController extends Controller
{
    public function index(Request $request)
    {
        $estado = $request->input('estado', 'pendientes');
        $notificado = $estado === 'notificadas' ? 1 : 0;

        $notas = auth()->user()->notas()
            ->where('notificado', $notificado)
            ->orderByRaw("ABS(TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(fecha, ' ', COALESCE(hora, '00:00:00'))))")
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Admin/Notas/Index', [
            'notas' => $notas,
            'filters' => [
                'estado' => $estado
            ]
        ]);
    }

    public function store(StoreNotaRequest $request)
    {
        $validated = $request->validated();

        auth()->user()->notas()->create($validated);

        return back()->with('success', 'Nota creada correctamente.');
    }

    public function edit(Nota $nota)
    {
        if ($nota->user_id !== auth()->id()) {
            abort(403);
        }

        return Inertia::render('Admin/Notas/Edit', [
            'nota' => $nota
        ]);
    }

    public function update(StoreNotaRequest $request, Nota $nota)
    {
        if ($nota->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validated();

        // Resetear notificado si cambian la hora u opciones de notificación
        if ($nota->fecha != $validated['fecha'] || 
            $nota->hora != $validated['hora'] || 
            $nota->notificacion_minutos_antes != $validated['notificacion_minutos_antes']) {
            $nota->notificado = false;
        }

        $nota->update($validated);

        return redirect()->route('admin.notas.index')->with('success', 'Nota actualizada correctamente.');
    }

    public function destroy(Nota $nota)
    {
        if ($nota->user_id !== auth()->id()) {
            abort(403);
        }
        
        $nota->delete();

        return redirect()->route('admin.notas.index')->with('success', 'Nota eliminada correctamente.');
    }
}
