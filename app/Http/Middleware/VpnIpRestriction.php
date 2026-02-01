<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VpnIpRestriction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar en el subdominio TU_DOMINIO
        // (Podríamos hacerlo más granular si hiciera falta)
        
        $ip = $request->ip();

        // Rango de la VPN: 10.0.0.0/24
        // También permitimos 127.0.0.1 por si acaso (CLI/Tests)
        if (!str_starts_with($ip, '10.0.0.') && $ip !== '127.0.0.1' && $ip !== '::1') {
            abort(403, "Acceso restringido. Por favor, conéctate a la VPN de Labs.");
        }

        return $next($request);
    }
}
