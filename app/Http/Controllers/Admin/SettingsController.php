<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'config' => [
                'precio_hora' => Configuracion::get('precio_hora', 0),
                'descuento_mantenimiento' => Configuracion::get('descuento_mantenimiento', 0),
            ],
        ]);
    }

    /**
     * Update the settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'precio_hora' => 'required|numeric|min:0',
            'descuento_mantenimiento' => 'required|numeric|min:0|max:100',
        ]);

        Configuracion::set('precio_hora', $request->precio_hora, 'Precio base por hora para los servicios');
        Configuracion::set('descuento_mantenimiento', $request->descuento_mantenimiento, 'Porcentaje de descuento aplicado a mantenimientos');

        return back()->with('success', 'Configuración actualizada correctamente.');
    }
}
