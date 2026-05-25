<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Http\Requests\Admin\ResumenHorasRequest;
use App\Services\ResumenHorasService;
use App\Models\Client;
use Illuminate\Http\Request;

class ResumenHoraController extends Controller
{
    protected ResumenHorasService $resumenHorasService;

    public function __construct(ResumenHorasService $resumenHorasService)
    {
        $this->resumenHorasService = $resumenHorasService;
    }

    /**
     * Display the resumen horas page.
     */
    public function index(ResumenHorasRequest $request)
    {
        $year = (int) $request->input('year', now()->year);
        $clientId = $request->input('client_id');
        $tipoServicio = $request->input('tipo_servicio');
        
        $filters = [
            'year' => $year,
            'client_id' => $clientId,
            'tipo_servicio' => $tipoServicio,
        ];
        
        $data = $this->resumenHorasService->getResumenAnual($year, $clientId, $tipoServicio);
        
        return Inertia::render('Admin/Horas/Index', [
            'resumenMensual' => $data['resumenMensual'],
            'stats' => $data['stats'],
            'filters' => $filters,
        ]);
    }

    /**
     * Devuelve el detalle (los registros) de un mes en particular.
     */
    public function detalle(ResumenHorasRequest $request, $month)
    {
        $year = (int) $request->input('year', now()->year);
        $clientId = $request->input('client_id');
        $tipoServicio = $request->input('tipo_servicio');

        $detalle = $this->resumenHorasService->getDetalleMes($year, (int)$month, $clientId, $tipoServicio);

        return response()->json(['detalle' => $detalle]);
    }
}
