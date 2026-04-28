<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseFactura;
use App\Http\Requests\StorePurchaseFacturaRequest;
use App\Http\Requests\UpdatePurchaseFacturaRequest;
use App\Services\PurchaseFacturaService;
use App\Services\GoogleDriveDocumentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseFacturaController extends Controller
{
    private PurchaseFacturaService $purchaseService;
    private GoogleDriveDocumentService $googleDriveService;

    public function __construct(
        PurchaseFacturaService $purchaseService,
        GoogleDriveDocumentService $googleDriveService
    ) {
        $this->purchaseService = $purchaseService;
        $this->googleDriveService = $googleDriveService;
    }

    public function index(Request $request)
    {
        $query = PurchaseFactura::query();

        // Búsqueda
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('provider_name', 'like', "%{$search}%")
                  ->orWhere('number', 'like', "%{$search}%");
            });
        }

        // Filtro de Proveedor
        if ($request->has('provider') && !empty($request->get('provider'))) {
            $query->where('provider_name', $request->get('provider'));
        }

        // Filtros de Fecha (por defecto, este año)
        $dateFrom = $request->has('date_from') ? $request->get('date_from') : now()->startOfYear()->toDateString();
        $dateTo = $request->has('date_to') ? $request->get('date_to') : now()->endOfYear()->toDateString();

        if (!empty($dateFrom)) {
            $query->whereDate('date', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('date', '<=', $dateTo);
        }

        // Calculate totals dynamically based on filters (avoiding N+1 by using aggregate sum)
        $totalsQuery = clone $query;
        $totals = [
            'net_amount' => (float) $totalsQuery->sum('net_amount'),
            'tax_amount' => (float) $totalsQuery->sum('tax_amount'),
            'total' => (float) $totalsQuery->sum('total'),
        ];

        // Ordenación
        $sort = $request->input('sort', 'date');
        $direction = $request->input('direction', 'desc');
        $allowedSorts = ['number', 'provider_name', 'date', 'net_amount', 'tax_amount', 'total', 'status'];
        
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'date';
        }
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $facturas = $query->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        $providers = PurchaseFactura::select('provider_name')
            ->distinct()
            ->orderBy('provider_name')
            ->pluck('provider_name');

        return Inertia::render('Admin/PurchaseFacturas/Index', [
            'facturas' => $facturas,
            'providers' => $providers,
            'totals' => $totals,
            'filters' => array_merge($request->only(['search', 'provider', 'sort', 'direction']), [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]),
        ]);
    }

    public function store(StorePurchaseFacturaRequest $request)
    {
        $result = $this->purchaseService->procesarFacturaDesdePdf($request->file('file'));

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }

    public function update(UpdatePurchaseFacturaRequest $request, PurchaseFactura $purchaseFactura)
    {
        $this->purchaseService->actualizarFactura($purchaseFactura, $request->validated());
        return redirect()->back()->with('success', 'Factura de compra actualizada.');
    }

    public function destroy(PurchaseFactura $purchaseFactura)
    {
        $this->purchaseService->eliminarFactura($purchaseFactura);
        return redirect()->back()->with('success', 'Factura eliminada.');
    }

    public function showPdf(PurchaseFactura $purchaseFactura)
    {
        if (!$purchaseFactura->google_drive_file_id) {
            abort(404);
        }

        $content = $this->googleDriveService->getDocumentContent('google_facturas', $purchaseFactura->google_drive_file_id);
        
        if (!$content) {
            abort(500, 'No se pudo descargar el archivo de Google Drive.');
        }

        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $purchaseFactura->number . '.pdf"');
    }

    public function confirmOverwrite(PurchaseFactura $purchaseFactura)
    {
        $this->purchaseService->resolverDuplicado($purchaseFactura);
        
        return redirect()->back()->with('success', "Factura corregida.");
    }
}
