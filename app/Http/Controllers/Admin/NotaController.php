<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Nota;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotaController extends Controller
{
    public function index()
    {
        $notas = auth()->user()->notas()->latest()->paginate(10);
        return Inertia::render('Admin/Notas/Index', [
            'notas' => $notas
        ]);
    }

    public function store(Request $request)
    {
        if ($request->has('hora') && strlen($request->hora) > 5) {
            $request->merge(['hora' => substr($request->hora, 0, 5)]);
        }

        $validated = $request->validate([
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'comentario' => 'required|string',
            'notificacion_minutos_antes' => 'required|integer|min:-1',
        ]);

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

    public function update(Request $request, Nota $nota)
    {
        if ($nota->user_id !== auth()->id()) {
            abort(403);
        }

        if ($request->has('hora') && strlen($request->hora) > 5) {
            $request->merge(['hora' => substr($request->hora, 0, 5)]);
        }

        $validated = $request->validate([
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'comentario' => 'required|string',
            'notificacion_minutos_antes' => 'required|integer|min:-1',
        ]);

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
