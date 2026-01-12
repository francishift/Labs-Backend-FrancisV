<?php

namespace App\Http\Controllers\Admin\Holded;

use App\Http\Controllers\Controller;
use App\Services\HoldedService;
use Inertia\Inertia;
use Illuminate\Http\Request;

use App\Models\Presupuesto;

class PresupuestoController extends Controller
{
    protected HoldedService $holdedService;

    public function __construct(HoldedService $holdedService)
    {
        $this->holdedService = $holdedService;
    }

    public function index(Request $request)
    {
        $start = $request->input('start', '2025-01-01');
        $end = $request->input('end', date('Y-m-d'));

        // Convert dates to timestamps for Holded API
        $startTimestamp = strtotime($start . ' 00:00:00');
        $endTimestamp = strtotime($end . ' 23:59:59');

        // Sync with Holded (updates local database)
        $syncResult = $this->holdedService->syncDocuments('estimate', [
            'starttmp' => $startTimestamp,
            'endtmp' => $endTimestamp,
        ]);

        // Fetch from local database
        $presupuestos = Presupuesto::whereBetween('date', [$startTimestamp, $endTimestamp])
            ->orderBy('date', 'desc')
            ->get();

        return Inertia::render('Admin/Holded/Presupuestos/Index', [
            'presupuestos' => $presupuestos,
            'errorMessage' => $syncResult['error'] ?? null,
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
