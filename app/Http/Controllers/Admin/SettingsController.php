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
                'porcentaje_software' => Configuracion::get('porcentaje_software', 5),
                'empresa_nombre' => Configuracion::get('empresa_nombre', ''),
                'empresa_nif' => Configuracion::get('empresa_nif', ''),
                'empresa_direccion' => Configuracion::get('empresa_direccion', ''),
                'empresa_email' => Configuracion::get('empresa_email', ''),
                'empresa_telefono' => Configuracion::get('empresa_telefono', ''),
                'empresa_banco_nombre' => Configuracion::get('empresa_banco_nombre', ''),
                'empresa_banco_iban' => Configuracion::get('empresa_banco_iban', ''),
                'default_iva' => Configuracion::get('default_iva', 21),
                'default_irpf' => Configuracion::get('default_irpf', 0),
                'default_vencimiento_dias' => Configuracion::get('default_vencimiento_dias', 30),
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
            'porcentaje_software' => 'required|numeric|min:0|max:100',
            'empresa_nombre' => 'nullable|string',
            'empresa_nif' => 'nullable|string',
            'empresa_direccion' => 'nullable|string',
            'empresa_email' => 'nullable|email',
            'empresa_telefono' => 'nullable|string',
            'empresa_banco_nombre' => 'nullable|string',
            'empresa_banco_iban' => 'nullable|string',
            'default_iva' => 'required|numeric|min:0|max:100',
            'default_irpf' => 'required|numeric|min:0|max:100',
            'default_vencimiento_dias' => 'required|numeric|min:0',
        ]);

        Configuracion::set('precio_hora', $request->precio_hora, 'Precio base por hora para los servicios');
        Configuracion::set('descuento_mantenimiento', $request->descuento_mantenimiento, 'Porcentaje de descuento aplicado a mantenimientos');
        Configuracion::set('porcentaje_software', $request->porcentaje_software, 'Porcentaje de coste software/hosting aplicado globalmente');
        Configuracion::set('empresa_nombre', $request->empresa_nombre, 'Nombre de la empresa facturadora');
        Configuracion::set('empresa_nif', $request->empresa_nif, 'NIF/CIF de la empresa facturadora');
        Configuracion::set('empresa_direccion', $request->empresa_direccion, 'Dirección completa de la empresa facturadora');
        Configuracion::set('empresa_email', $request->empresa_email, 'Email de la empresa facturadora');
        Configuracion::set('empresa_telefono', $request->empresa_telefono, 'Teléfono de contacto');
        Configuracion::set('empresa_banco_nombre', $request->empresa_banco_nombre, 'Nombre del Banco');
        Configuracion::set('empresa_banco_iban', $request->empresa_banco_iban, 'Número de cuenta IBAN');
        Configuracion::set('default_iva', $request->default_iva, 'IVA aplicable por defecto en presupuestos y facturas');
        Configuracion::set('default_irpf', $request->default_irpf, 'IRPF aplicable por defecto en presupuestos y facturas');
        Configuracion::set('default_vencimiento_dias', $request->default_vencimiento_dias, 'Días de vencimiento por defecto');

        return back()->with('success', 'Configuración actualizada correctamente.');
    }
}
