<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Presupuesto;
use App\Models\Client;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Mail;
use App\Mail\PresupuestoPdfMail;
use App\Models\Configuracion;
use App\Enums\PresupuestoStatus;

class PresupuestoController extends Controller
{
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
            ->paginate(15)
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
        $clientes = Client::orderBy('name')->get(['id', 'name', 'cif_nif', 'email']);
        return Inertia::render('Admin/Presupuestos/Create', [
            'clientes' => $clientes,
            'defaultIva' => (float) Configuracion::get('default_iva', 21),
            'defaultIrpf' => (float) Configuracion::get('default_irpf', 0),
            'defaultVencimientoDias' => (int) Configuracion::get('default_vencimiento_dias', 30),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'lineas' => 'required|array|min:1',
            'lineas.*.concepto' => 'required|string',
            'lineas.*.descripcion' => 'nullable|string',
            'lineas.*.cantidad' => 'required|numeric|min:0.01',
            'lineas.*.precio_unitario' => 'required|numeric',
            'lineas.*.porcentaje_iva' => 'required|numeric|min:0',
            'lineas.*.porcentaje_irpf' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $lastPresupuesto = Presupuesto::where('number', 'like', 'PR-%')
            ->orderByRaw('CAST(SUBSTRING(number, 4) AS UNSIGNED) DESC')
            ->first();

        $nextNum = 1;
        if ($lastPresupuesto && preg_match('/^PR-(\d+)$/', $lastPresupuesto->number, $matches)) {
            $nextNum = intval($matches[1]) + 1;
        }
        $number = sprintf("PR-%d", $nextNum);

        $presupuesto = Presupuesto::create([
            'number' => $number,
            'client_id' => $request->client_id,
            'date' => strtotime($request->date),
            'due_date' => $request->due_date ? date('Y-m-d', strtotime($request->due_date)) : null,
            'status' => PresupuestoStatus::PENDING,
            'notes' => $request->notes,
            'description' => $request->description,
        ]);

        $this->syncLineas($presupuesto, $request->lineas);

        defer(fn () => $this->saveToDrive($presupuesto));

        return redirect()->route('admin.presupuestos.index')->with('success', 'Presupuesto creado con éxito.');
    }

    public function show(Presupuesto $presupuesto)
    {
        $presupuesto->load(['lineas', 'cliente']);
        
        return Inertia::render('Admin/Presupuestos/Show', [
            'presupuesto' => $presupuesto,
        ]);
    }

    public function edit(Presupuesto $presupuesto)
    {
        $presupuesto->load('lineas');
        $clientes = Client::orderBy('name')->get(['id', 'name', 'cif_nif', 'email']);
        
        return Inertia::render('Admin/Presupuestos/Edit', [
            'presupuesto' => $presupuesto,
            'clientes' => $clientes,
            'statuses' => PresupuestoStatus::options(),
            'defaultIva' => (float) Configuracion::get('default_iva', 21),
            'defaultIrpf' => (float) Configuracion::get('default_irpf', 0),
        ]);
    }

    public function update(Request $request, Presupuesto $presupuesto)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'lineas' => 'required|array|min:1',
            'lineas.*.concepto' => 'required|string',
            'lineas.*.descripcion' => 'nullable|string',
            'lineas.*.cantidad' => 'required|numeric|min:0.01',
            'lineas.*.precio_unitario' => 'required|numeric',
            'lineas.*.porcentaje_iva' => 'required|numeric|min:0',
            'lineas.*.porcentaje_irpf' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'status' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $presupuesto->update([
            'client_id' => $request->client_id,
            'date' => strtotime($request->date),
            'due_date' => $request->due_date ? date('Y-m-d', strtotime($request->due_date)) : null,
            'status' => $request->has('status') ? PresupuestoStatus::tryFrom((int)$request->status) : $presupuesto->status,
            'notes' => $request->notes,
            'description' => $request->description,
        ]);

        $this->syncLineas($presupuesto, $request->lineas);

        defer(fn () => $this->saveToDrive($presupuesto));

        // Redirigir a vista show
        return redirect()->route('admin.presupuestos.show', $presupuesto->id)->with('success', 'Presupuesto actualizado con éxito.');
    }

    public function destroy(Presupuesto $presupuesto)
    {
        $presupuesto->updateQuietly(['status' => \App\Enums\PresupuestoStatus::CANCELED]);

        defer(fn () => $this->saveToDrive($presupuesto));

        return redirect()->route('admin.presupuestos.index')->with('success', 'Presupuesto anulado correctamente.');
    }

    public function updateStatus(Request $request, Presupuesto $presupuesto)
    {
        $validated = $request->validate(['status' => 'required|integer']);
        $presupuesto->updateQuietly(['status' => \App\Enums\PresupuestoStatus::tryFrom($validated['status'])]);
        defer(fn () => $this->saveToDrive($presupuesto));
        return redirect()->back()->with('success', 'Estado modificado correctamente.');
    }

    public function reactivate(Presupuesto $presupuesto)
    {
        $presupuesto->updateQuietly(['status' => \App\Enums\PresupuestoStatus::PENDING]);
        defer(fn () => $this->saveToDrive($presupuesto));
        return redirect()->route('admin.presupuestos.index')->with('success', 'Presupuesto reactivado correctamente.');
    }

    private function syncLineas(Presupuesto $presupuesto, array $lineas)
    {
        $presupuesto->lineas()->delete();

        $subtotal = 0;
        $tax_amount = 0;
        $irpf_amount = 0;

        foreach ($lineas as $linea) {
            $cantidad = (float) $linea['cantidad'];
            $precio = (float) $linea['precio_unitario'];
            $ivaPct = (float) ($linea['porcentaje_iva'] ?? 0);
            $irpfPct = (float) ($linea['porcentaje_irpf'] ?? 0);

            $lineaTotal = $cantidad * $precio;
            
            $subtotal += $lineaTotal;
            $tax_amount += $lineaTotal * ($ivaPct / 100);
            $irpf_amount += $lineaTotal * ($irpfPct / 100);

            $presupuesto->lineas()->create([
                'concepto' => $linea['concepto'],
                'descripcion' => $linea['descripcion'] ?? null,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'porcentaje_iva' => $ivaPct,
                'porcentaje_irpf' => $irpfPct,
                'total_linea' => $lineaTotal,
            ]);
        }

        $presupuesto->updateQuietly([
            'subtotal' => $subtotal,
            'tax_amount' => $tax_amount,
            'irpf_amount' => $irpf_amount,
            'total' => $subtotal + $tax_amount - $irpf_amount,
        ]);
    }

    public function exportPdf(Presupuesto $presupuesto, Request $request)
    {
        $safeDocNumber = str_replace(['/', '\\'], '-', $presupuesto->number ?? $presupuesto->id);
        $disposition = $request->has('download') ? 'attachment' : 'inline';

        if ($presupuesto->google_drive_file_id) {
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
                \Log::warning('No se pudo traer el PDF original guardado en Drive. Generando fallback local: ' . $e->getMessage());
            }
        }

        $pdfOutput = $this->generatePdfBytes($presupuesto);

        return response($pdfOutput)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition . '; filename="' . $safeDocNumber . '.pdf"')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0')
            ->header('Pragma', 'no-cache');
    }

    public function sendPdfEmail(Presupuesto $presupuesto, Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string'
        ]);
        
        $pdfOutput = $this->generatePdfBytes($presupuesto);

        Mail::to($request->email)->send(new PresupuestoPdfMail($presupuesto, $pdfOutput, $request->message));

        return back()->with('success', 'Presupuesto enviado por correo electrónico a ' . $request->email);
    }

    private function generatePdfBytes(Presupuesto $presupuesto)
    {
        $presupuesto->load(['lineas', 'cliente']);
        
        $logoPath = public_path('img/logo.png');
        if (!file_exists($logoPath)) {
            $logoPath = public_path('logo-icono.png'); // Fallback
        }
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
        }

        $config = [
            'empresa_nombre' => Configuracion::get('empresa_nombre', ''),
            'empresa_nif' => Configuracion::get('empresa_nif', ''),
            'empresa_direccion' => Configuracion::get('empresa_direccion', ''),
            'empresa_email' => Configuracion::get('empresa_email', ''),
            'empresa_telefono' => Configuracion::get('empresa_telefono', ''),
            'empresa_banco_nombre' => Configuracion::get('empresa_banco_nombre', ''),
            'empresa_banco_iban' => Configuracion::get('empresa_banco_iban', ''),
            'default_vencimiento_dias' => Configuracion::get('default_vencimiento_dias', 30),
        ];

        $pdf = Pdf::loadView('pdf.presupuesto', [
            'presupuesto' => $presupuesto,
            'logoBase64' => $logoBase64,
            'configList' => $config
        ]);
        return $pdf->output();
    }

    public function saveToDrive(Presupuesto $presupuesto)
    {
        try {
            $pdfBinary = $this->generatePdfBytes($presupuesto);
            
            $year = date('Y', is_numeric($presupuesto->date) ? $presupuesto->date : strtotime($presupuesto->date));
            $rootId = env('GOOGLE_DRIVE_FOLDER_ID_PRESUPUESTOS');
            
            $adapter = Storage::disk('google_presupuestos')->getAdapter();
            $service = $adapter->getService();

            $optParams = [
                'q' => "'$rootId' in parents and mimeType = 'application/vnd.google-apps.folder' and name = '$year' and trashed = false",
                'fields' => 'files(id, name)'
            ];
            $files = $service->files->listFiles($optParams)->getFiles();

            $folderId = count($files) > 0 ? $files[0]->getId() : $service->files->create(new DriveFile([
                'name' => $year,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$rootId]
            ]), ['fields' => 'id'])->getId();

            $presupuesto->loadMissing('cliente');
            $clientName = $presupuesto->cliente ? $presupuesto->cliente->name : 'Cliente';
            // Limpiar caracteres no válidos para nombres de archivo en Drive
            $safeClientName = str_replace(['/', '\\'], '-', $clientName);
            $safeDocNumber = str_replace(['/', '\\'], '-', $presupuesto->number ?? $presupuesto->id);
            
            $fileNameSuffix = '';
            if ($presupuesto->status === PresupuestoStatus::CANCELED) {
                $fileNameSuffix = ' (Anulado)';
            } elseif ($presupuesto->status === PresupuestoStatus::REJECTED) {
                $fileNameSuffix = ' (Rechazado)';
            }
            $fileName = "{$safeDocNumber} - {$safeClientName}{$fileNameSuffix}.pdf";

            if ($presupuesto->google_drive_file_id) {
                try {
                    $file = $service->files->get($presupuesto->google_drive_file_id, ['fields' => 'parents, trashed']);
                    if (!$file->getTrashed()) {
                        $previousParents = implode(',', $file->getParents());
                        $service->files->update($presupuesto->google_drive_file_id, new DriveFile(['name' => $fileName]), [
                            'data' => $pdfBinary,
                            'mimeType' => 'application/pdf',
                            'uploadType' => 'multipart',
                            'addParents' => $folderId,
                            'removeParents' => $previousParents,
                            'fields' => 'id'
                        ]);
                        return; // Exito actualizando el existente
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Drive file not found by ID, continuing: ' . $e->getMessage());
                }
            }

            $fileOptParams = [
                'q' => "'$folderId' in parents and name = '$fileName' and trashed = false",
                'fields' => 'files(id)'
            ];
            $existingFiles = $service->files->listFiles($fileOptParams)->getFiles();

            if (count($existingFiles) > 0) {
                $fileId = $existingFiles[0]->getId();
                $service->files->update($fileId, new DriveFile(), [
                    'data' => $pdfBinary,
                    'mimeType' => 'application/pdf',
                    'uploadType' => 'multipart',
                    'fields' => 'id'
                ]);
            } else {
                $fileId = $service->files->create(new DriveFile([
                    'name' => $fileName,
                    'parents' => [$folderId]
                ]), [
                    'data' => $pdfBinary,
                    'mimeType' => 'application/pdf',
                    'uploadType' => 'multipart',
                    'fields' => 'id'
                ])->getId();
            }
            
            $presupuesto->updateQuietly(['google_drive_file_id' => $fileId]);
            
        } catch (\Throwable $e) {
            \Log::error('Fallo al subir presupuesto nativo a Google Drive: ' . $e->getMessage());
        }
    }
}
