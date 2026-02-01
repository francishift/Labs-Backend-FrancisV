<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ] : null,

                'roles' => $user ? $user->getRoleNames()->values() : [],
            ],

            'proyectos_list' => fn () => $user && $user->hasAnyRole(['admin', 'coordinador']) 
                ? \App\Models\Proyecto::orderBy('proyecto')->get(['id', 'proyecto'])
                : [],

            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'vpn_config' => $request->session()->get('vpn_config'),
            ],
            'config' => [
                'precio_hora' => \App\Models\Configuracion::get('precio_hora', 0),
                'descuento_mantenimiento' => \App\Models\Configuracion::get('descuento_mantenimiento', 0),
            ],
        ];
    }
}