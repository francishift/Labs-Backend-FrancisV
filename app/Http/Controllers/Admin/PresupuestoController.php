<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StorePresupuestoRequest;
use App\Http\Requests\UpdatePresupuestoRequest;
use App\Http\Requests\SendPresupuestoEmailRequest;
use App\Models\Presupuesto;
use App\Models\Client;
use App\Models\Configuracion;
use App\Enums\PresupuestoStatus;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\PresupuestoPdfMail;
use App\Services\PresupuestoService;
use App\Services\DocumentPdfService;
use Illuminate\Support\Facades\Log;

class PresupuestoController extends Controller
{
    private PresupuestoService $presupuestoService;
    private DocumentPdfService $pdfService;

    public function __construct(
        PresupuestoService $presupuestoService,
        DocumentPdfService $pdfService
    ) {
        $this->presupuestoService = $presupuestoService;
        $this->pdfService = $pdfService;
    }

    public function index(Request $request)
    {
        $query = Presupuesto::with('cliente');

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

        // Filtros de Fecha (por defecto, últimos 12 meses)
        $dateFrom = $request->has('date_from') ? $request->get('date_from') : now()->subYear()->toDateString();
        $dateTo = $request->has('date_to') ? $request->get('date_to') : now()->toDateString();

        if (!empty($dateFrom)) {
            $query->where('date', '>=', strtotime($dateFrom));
        }
        if (!empty($dateTo)) {
            $query->where('date', '<=', strtotime($dateTo . ' 23:59:59'));
        }

        // Solo sumar Pendientes y Aprobados
        $totalsQuery = clone $query;
        $totalsQuery->whereIn('status', [PresupuestoStatus::PENDING->value, PresupuestoStatus::APPROVED->value]);
        
        $totals = [
            'net_amount' => (float) $totalsQuery->sum('subtotal'),
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

        $presupuestos = $query->orderBy($sort, $direction)
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($presupuesto) => [
                'id' => $presupuesto->id,
                'number' => $presupuesto->number,
                'date' => $presupuesto->date,
                'total' => $presupuesto->total,
                'status' => $presupuesto->status,
                'contact_name' => collect([$presupuesto->cliente])->filter()->first()?->name ?? 'Sin Cliente',
            ]);

        $clientsOptions = Client::select('name')->orderBy('name')->pluck('name');

        return Inertia::render('Admin/Presupuestos/Index', [
            'presupuestos' => $presupuestos,
            'clients' => $clientsOptions,
            'statuses' => PresupuestoStatus::options(),
            'totals' => $totals,
            'filters' => array_merge($request->only(['search', 'client', 'status', 'sort', 'direction']), [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Presupuestos/Create', [
            'defaultIva' => (float) Configuracion::get('default_iva', 21),
            'defaultIrpf' => (float) Configuracion::get('default_irpf', 0),
            'defaultVencimientoDias' => (int) Configuracion::get('default_vencimiento_dias', 30),
        ]);
    }

    public function store(StorePresupuestoRequest $request)
    {
        $presupuesto = $this->presupuestoService->crearPresupuesto($request->validated());

        defer(fn () => $this->presupuestoService->guardarEnDriveAsync($presupuesto));

        return redirect()->route('admin.presupuestos.index')->with('success', 'Presupuesto creado con éxito.');
    }

    public function show(Presupuesto $presupuesto)
    {
        $presupuesto->loadMissing(['lineas', 'cliente']);
        
        return Inertia::render('Admin/Presupuestos/Show', [
            'presupuesto' => $presupuesto,
        ]);
    }

    public function edit(Presupuesto $presupuesto)
    {
        $presupuesto->loadMissing('lineas');
        
        return Inertia::render('Admin/Presupuestos/Edit', [
            'presupuesto' => $presupuesto,
            'statuses' => PresupuestoStatus::options(),
            'defaultIva' => (float) Configuracion::get('default_iva', 21),
            'defaultIrpf' => (float) Configuracion::get('default_irpf', 0),
        ]);
    }

    public function update(UpdatePresupuestoRequest $request, Presupuesto $presupuesto)
    {
        $presupuesto = $this->presupuestoService->actualizarPresupuesto($presupuesto, $request->validated());

        defer(fn () => $this->presupuestoService->guardarEnDriveAsync($presupuesto));

        return redirect()->route('admin.presupuestos.index')->with('success', 'Presupuesto actualizado con éxito.');
    }

    public function destroy(Presupuesto $presupuesto)
    {
        $presupuesto->updateQuietly(['status' => PresupuestoStatus::CANCELED]);

        defer(fn () => $this->presupuestoService->guardarEnDriveAsync($presupuesto));

        return redirect()->route('admin.presupuestos.index')->with('success', 'Presupuesto anulado correctamente.');
    }

    public function updateStatus(Request $request, Presupuesto $presupuesto)
    {
        $validated = $request->validate(['status' => 'required|integer']);
        $presupuesto->updateQuietly(['status' => PresupuestoStatus::tryFrom($validated['status'])]);
        
        defer(fn () => $this->presupuestoService->guardarEnDriveAsync($presupuesto));
        
        return redirect()->back()->with('success', 'Estado modificado correctamente.');
    }

    public function reactivate(Presupuesto $presupuesto)
    {
        $presupuesto->updateQuietly(['status' => PresupuestoStatus::PENDING]);
        
        defer(fn () => $this->presupuestoService->guardarEnDriveAsync($presupuesto));
        
        return redirect()->route('admin.presupuestos.index')->with('success', 'Presupuesto reactivado correctamente.');
    }

    public function exportPdf(Presupuesto $presupuesto, Request $request)
    {
        $safeDocNumber = str_replace(['/', '\\'], '-', $presupuesto->number ?? $presupuesto->id);
        $disposition = $request->has('download') ? 'attachment' : 'inline';

        $isCurrentlySyncing = $presupuesto->updated_at && $presupuesto->updated_at->diffInSeconds(now()) < 10;

        // Intentar descargar de Google Drive si existe y no está sincronizándose
        if ($presupuesto->google_drive_file_id && !$isCurrentlySyncing) {
            try {
                $adapter = Storage::disk('google_presupuestos')->getAdapter();
                $service = $adapter->getService();
                $response = $service->files->get($presupuesto->google_drive_file_id, ['alt' => 'media']);
                $content = $response->getBody()->getContents();

                return response($content)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', $disposition . '; filename="' . $safeDocNumber . '.pdf"')
                    ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0')
                    ->header('Pragma', 'no-cache');
            } catch (\Exception $e) {
                Log::warning('No se pudo traer el PDF original guardado en Drive. Generando fallback local: ' . $e->getMessage());
            }
        }

        // Fallback: Generar el PDF dinámicamente usando el nuevo servicio
        $pdfOutput = $this->pdfService->generatePresupuestoPdf($presupuesto);

        return response($pdfOutput)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition . '; filename="' . $safeDocNumber . '.pdf"')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0')
            ->header('Pragma', 'no-cache');
    }

    public function sendPdfEmail(Presupuesto $presupuesto, SendPresupuestoEmailRequest $request)
    {
        $this->presupuestoService->enviarPresupuestoPorEmail($presupuesto, $request->validated(), auth()->user());

        return back()->with('success', 'Presupuesto enviado por correo electrónico a ' . $request->email);
    }
}
