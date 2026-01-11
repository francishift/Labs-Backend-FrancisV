<?php

namespace App\Http\Controllers\Admin\Holded;

use App\Http\Controllers\Controller;
use App\Services\HoldedService;
use Inertia\Inertia;
use Illuminate\Http\Request;

class PresupuestoController extends Controller
{
    protected HoldedService $holdedService;

    public function __construct(HoldedService $holdedService)
    {
        $this->holdedService = $holdedService;
    }

    public function index(Request $request)
    {
        $start = $request->input('start', date('Y-m-d', strtotime('-365 days')));
        $end = $request->input('end', date('Y-m-d'));

        // Convert dates to timestamps for Holded API
        $startTimestamp = strtotime($start . ' 00:00:00');
        $endTimestamp = strtotime($end . ' 23:59:59');

        $result = $this->holdedService->getDocuments('estimate', [
            'starttmp' => $startTimestamp,
            'endtmp' => $endTimestamp,
        ]);

        return Inertia::render('Admin/Holded/Presupuestos/Index', [
            'presupuestos' => $result['data'],
            'errorMessage' => $result['error'],
            'filters' => [
                'start' => $start,
                'end' => $end,
            ],
        ]);
    }

    public function downloadPdf(string $id)
    {
        $pdfBase64 = $this->holdedService->getDocumentPdf('estimate', $id);

        if (!$pdfBase64) {
            return back()->with('error', 'No se pudo recuperar el PDF de Holded.');
        }

        $pdfBinary = base64_decode($pdfBase64);

        return response($pdfBinary)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="presupuesto-' . $id . '.pdf"');
    }
}
