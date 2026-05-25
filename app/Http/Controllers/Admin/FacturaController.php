<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreFacturaRequest;
use App\Http\Requests\UpdateFacturaRequest;
use App\Http\Requests\SendFacturaEmailRequest;
use App\Models\Factura;
use App\Models\Client;
use App\Models\Configuracion;
use App\Models\Proyecto;
use App\Enums\FacturaStatus;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\FacturaPdfMail;
use App\Services\FacturaService;
use App\Services\DocumentPdfService;
use Illuminate\Support\Facades\Log;

class FacturaController extends Controller
{
    private FacturaService $facturaService;
    private DocumentPdfService $pdfService;

    public function __construct(
        FacturaService $facturaService,
        DocumentPdfService $pdfService
    ) {
        $this->facturaService = $facturaService;
        $this->pdfService = $pdfService;
    }

    public function index(Request $request)
    {
        $query = Factura::with('cliente');

        // Búsqueda general
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('cliente', fn($c) => $c->where('name', 'like', "%{$search}%"))
                  ->orWhere('number', 'like', "%{$search}%");
            });
        }

        // Filtro de Cliente/Contacto
        if ($request->has('client') && !empty($request->get('client'))) {
            $query->whereHas('cliente', fn($c) => $c->where('name', $request->get('client')));
        }

        // Filtro de estado
        if ($request->has('status') && $request->get('status') !== '') {
            $query->where('status', (int) $request->get('status'));
        }

        // Filtros de Fecha (por defecto, año actual)
        $dateFrom = $request->has('date_from') ? $request->get('date_from') : now()->startOfYear()->toDateString();
        $dateTo = $request->has('date_to') ? $request->get('date_to') : now()->endOfYear()->toDateString();

        if (!empty($dateFrom)) {
            $query->where('date', '>=', strtotime($dateFrom));
        }
        if (!empty($dateTo)) {
            $query->where('date', '<=', strtotime($dateTo . ' 23:59:59'));
        }

        // Solo sumar Pagadas, Parciales y Pendientes (excluir Anuladas)
        $totalsQuery = clone $query;
        $totalsQuery->where('status', '!=', \App\Enums\FacturaStatus::CANCELED->value);
        
        $totals = [
            'subtotal' => (float) $totalsQuery->sum('subtotal'),
            'tax_amount' => (float) $totalsQuery->sum('tax_amount'),
            'total' => (float) $totalsQuery->sum('total'),
        ];

        // Ordenación
        $sort = $request->input('sort', 'date');
        $direction = $request->input('direction', 'desc');
        
        $allowedSorts = ['number', 'date', 'subtotal', 'tax_amount', 'total', 'status'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'date';
        }
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $facturas = $query->orderBy($sort, $direction)
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($factura) => [
                'id' => $factura->id,
                'number' => $factura->number,
                'date' => $factura->date,
                'due_date' => $factura->due_date,
                'subtotal' => $factura->subtotal,
                'tax_amount' => $factura->tax_amount,
                'irpf_amount' => $factura->irpf_amount,
                'total' => $factura->total,
                'status' => $factura->status,
                'contact_name' => collect([$factura->cliente])->filter()->first()?->name ?? 'Sin Cliente',
            ]);

        $clientsOptions = Client::select('name')->orderBy('name')->pluck('name');

        return Inertia::render('Admin/Facturas/Index', [
            'facturas' => $facturas,
            'clients' => $clientsOptions,
            'statuses' => FacturaStatus::options(),
            'totals' => $totals,
            'filters' => array_merge($request->only(['search', 'client', 'status', 'sort', 'direction']), [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Facturas/Create', [
            'defaultIva' => (float) Configuracion::get('default_iva', 21),
            'defaultIrpf' => (float) Configuracion::get('default_irpf', 0),
            'defaultVencimientoDias' => (int) Configuracion::get('default_vencimiento_dias', 30),
        ]);
    }

    public function store(StoreFacturaRequest $request)
    {
        $factura = $this->facturaService->crearFactura($request->validated());

        defer(fn () => $this->facturaService->guardarEnDriveAsync($factura));

        return redirect()->route('admin.facturas.index')->with('success', 'Factura creada con éxito.');
    }

    public function show(Factura $factura)
    {
        $factura->loadMissing(['lineas', 'cliente']);
        
        return Inertia::render('Admin/Facturas/Show', [
            'factura' => $factura,
        ]);
    }

    public function edit(Factura $factura)
    {
        $factura->loadMissing('lineas');
        
        return Inertia::render('Admin/Facturas/Edit', [
            'factura' => $factura,
            'statuses' => FacturaStatus::options(),
            'defaultIva' => (float) Configuracion::get('default_iva', 21),
            'defaultIrpf' => (float) Configuracion::get('default_irpf', 0),
        ]);
    }

    public function update(UpdateFacturaRequest $request, Factura $factura)
    {
        $factura = $this->facturaService->actualizarFactura($factura, $request->validated());

        defer(fn () => $this->facturaService->guardarEnDriveAsync($factura));

        return redirect()->route('admin.facturas.index')->with('success', 'Factura actualizada con éxito.');
    }

    public function destroy(Factura $factura)
    {
        $factura->updateQuietly(['status' => FacturaStatus::CANCELED]);

        defer(fn () => $this->facturaService->guardarEnDriveAsync($factura));

        return redirect()->route('admin.facturas.index')->with('success', 'Factura anulada exitosamente.');
    }

    public function updateStatus(Request $request, Factura $factura)
    {
        $validated = $request->validate(['status' => 'required|integer']);
        $factura->updateQuietly(['status' => FacturaStatus::tryFrom($validated['status'])]);
        
        defer(fn () => $this->facturaService->guardarEnDriveAsync($factura));
        
        return redirect()->back()->with('success', 'Estado modificado correctamente.');
    }

    public function reactivate(Factura $factura)
    {
        $factura->updateQuietly(['status' => FacturaStatus::PENDING]);

        defer(fn () => $this->facturaService->guardarEnDriveAsync($factura));

        return redirect()->route('admin.facturas.index')->with('success', 'Factura reactivada correctamente.');
    }

    public function duplicate(Factura $factura)
    {
        $nuevaFactura = $this->facturaService->duplicarFactura($factura);

        defer(fn () => clone $nuevaFactura->loadMissing('cliente') && $this->facturaService->guardarEnDriveAsync($nuevaFactura));

        return redirect()->route('admin.facturas.edit', $nuevaFactura->id)
            ->with('success', 'Factura duplicada correctamente. Revisa los datos antes de guardarla.');
    }

    public function exportPdf(Factura $factura, Request $request)
    {
        $safeDocNumber = str_replace(['/', '\\'], '-', $factura->number ?? $factura->id);
        $disposition = $request->has('download') ? 'attachment' : 'inline';

        $isCurrentlySyncing = $factura->updated_at && $factura->updated_at->diffInSeconds(now()) < 10;

        // Intentar descargar de Google Drive si existe y no está sincronizándose
        if ($factura->google_drive_file_id && !$isCurrentlySyncing) {
            try {
                $adapter = Storage::disk('google_facturas')->getAdapter();
                $service = $adapter->getService();
                $response = $service->files->get($factura->google_drive_file_id, ['alt' => 'media']);
                $content = $response->getBody()->getContents();

                return response($content)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', $disposition . '; filename="' . $safeDocNumber . '.pdf"')
                    ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0')
                    ->header('Pragma', 'no-cache');
            } catch (\Throwable $e) {
                Log::warning('No se pudo traer el PDF original guardado en Drive. Generando fallback local: ' . $e->getMessage());
            }
        }

        // Fallback: Generar el PDF dinámicamente usando el nuevo servicio
        $pdfOutput = $this->pdfService->generateFacturaPdf($factura);

        return response($pdfOutput)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition . '; filename="' . $safeDocNumber . '.pdf"')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0')
            ->header('Pragma', 'no-cache');
    }

    public function sendPdfEmail(Factura $factura, SendFacturaEmailRequest $request)
    {
        $this->facturaService->enviarFacturaPorEmail($factura, $request->validated(), auth()->user());

        return back()->with('success', 'Factura enviada por correo electrónico a ' . $request->email);
    }
}
