<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Proyecto;
use App\Models\Mantenimiento;
use App\Models\Extension;
use Illuminate\Http\Request;

class DropdownController extends Controller
{
    public function clientes(Request $request)
    {
        $query = Client::select(['id', 'name', 'cif_nif', 'email']);
        
        if ($request->filled('id')) {
            $query->where('id', $request->input('id'));
        } elseif ($request->filled('q')) {
            $query->where('name', 'like', "%{$request->input('q')}%");
        }
        
        $clientes = $query->orderBy('name')->limit(15)->get();
        return $clientes->map(function ($c) {
            $c->display_name = $c->name . ($c->cif_nif ? " ({$c->cif_nif})" : ' (Sin NIF)');
            return $c;
        });
    }

    public function proyectos(Request $request)
    {
        $query = Proyecto::select(['id', 'proyecto']);
        
        if ($request->filled('id')) {
            $query->where('id', $request->input('id'));
        } elseif ($request->filled('q')) {
            $query->where('proyecto', 'like', "%{$request->input('q')}%");
        }
        
        return $query->orderBy('proyecto')->limit(15)->get();
    }

    public function mantenimientos(Request $request)
    {
        $query = Mantenimiento::select(['id', 'aplicacion']);
        
        if ($request->filled('id')) {
            $query->where('id', $request->input('id'));
        } elseif ($request->filled('q')) {
            $query->where('aplicacion', 'like', "%{$request->input('q')}%");
        }
        
        return $query->orderBy('aplicacion')->limit(15)->get();
    }

    public function extensiones(Request $request)
    {
        $query = Extension::select(['id', 'nombre', 'precio']);
        
        if ($request->filled('id')) {
            $query->where('id', $request->input('id'));
        } elseif ($request->filled('q')) {
            $query->where('nombre', 'like', "%{$request->input('q')}%");
        }
        
        return $query->orderBy('nombre')->limit(15)->get();
    }
}
