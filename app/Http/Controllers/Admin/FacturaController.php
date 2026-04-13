<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Factura;
use App\Models\Client;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Mail;
use App\Mail\FacturaPdfMail;
use App\Models\Configuracion;
use App\Enums\FacturaStatus;

class FacturaController extends Controller
{
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

        // Solo sumar Pagadas y Parciales (o pendientes, normalmente sumar todo o filtrar pendientes)
        $totalsQuery = clone $query;
        
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
            ->paginate(15)
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
        $clientes = Client::orderBy('name')->get(['id', 'name', 'cif_nif', 'email']);
        $proyectos = \App\Models\Proyecto::orderBy('proyecto')->get(['id', 'proyecto']);
        return Inertia::render('Admin/Facturas/Create', [
            'clientes' => $clientes,
            'proyectos' => $proyectos,
            'defaultIva' => (float) Configuracion::get('default_iva', 21),
            'defaultIrpf' => (float) Configuracion::get('default_irpf', 0),
            'defaultVencimientoDias' => (int) Configuracion::get('default_vencimiento_dias', 30),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'proyecto_id' => 'nullable|exists:proyectos,id',
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

        $lastFactura = Factura::where('number', 'like', 'FV-%')
            ->orderByRaw('CAST(SUBSTRING(number, 4) AS UNSIGNED) DESC')
            ->first();

        $nextNum = 1;
        if ($lastFactura && preg_match('/^FV-(\d+)$/', $lastFactura->number, $matches)) {
            $nextNum = intval($matches[1]) + 1;
        }
        $number = sprintf("FV-%d", $nextNum);

        $factura = Factura::create([
            'number' => $number,
            'client_id' => $request->client_id,
            'proyecto_id' => $request->proyecto_id,
            'date' => strtotime($request->date),
            'due_date' => $request->due_date ? strtotime($request->due_date) : null,
            'status' => FacturaStatus::PENDING,
            'notes' => $request->notes,
            'description' => $request->description,
        ]);

        $this->syncLineas($factura, $request->lineas);
        defer(fn () => $this->saveToDrive($factura));

        return redirect()->route('admin.facturas.index')->with('success', 'Factura creada con éxito.');
    }

    public function show(Factura $factura)
    {
        $factura->load(['lineas', 'cliente']);
        
        return Inertia::render('Admin/Facturas/Show', [
            'factura' => $factura,
        ]);
    }

    public function edit(Factura $factura)
    {
        $factura->load('lineas');
        $clientes = Client::orderBy('name')->get(['id', 'name', 'cif_nif', 'email']);
        $proyectos = \App\Models\Proyecto::orderBy('proyecto')->get(['id', 'proyecto']);
        
        return Inertia::render('Admin/Facturas/Edit', [
            'factura' => $factura,
            'clientes' => $clientes,
            'proyectos' => $proyectos,
            'statuses' => FacturaStatus::options(),
            'defaultIva' => (float) Configuracion::get('default_iva', 21),
            'defaultIrpf' => (float) Configuracion::get('default_irpf', 0),
        ]);
    }

    public function update(Request $request, Factura $factura)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'proyecto_id' => 'nullable|exists:proyectos,id',
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

        $factura->update([
            'client_id' => $request->client_id,
            'proyecto_id' => $request->proyecto_id,
            'date' => strtotime($request->date),
            'due_date' => $request->due_date ? strtotime($request->due_date) : null,
            'status' => $request->has('status') ? FacturaStatus::tryFrom((int)$request->status) : $factura->status,
            'notes' => $request->notes,
            'description' => $request->description,
        ]);

        $this->syncLineas($factura, $request->lineas);
        defer(fn () => $this->saveToDrive($factura));

        // Redirigir a vista show
        return redirect()->route('admin.facturas.show', $factura->id)->with('success', 'Factura actualizada con éxito.');
    }

    public function destroy(Factura $factura)
    {
        $factura->updateQuietly(['status' => \App\Enums\FacturaStatus::CANCELED]);

        defer(fn () => $this->saveToDrive($factura));

        return redirect()->route('admin.facturas.index')->with('success', 'Factura anulada exitosamente.');
    }

    public function updateStatus(Request $request, Factura $factura)
    {
        $validated = $request->validate(['status' => 'required|integer']);
        $factura->updateQuietly(['status' => FacturaStatus::tryFrom($validated['status'])]);
        defer(fn () => $this->saveToDrive($factura));
        return redirect()->back()->with('success', 'Estado modificado correctamente.');
    }

    public function reactivate(Factura $factura)
    {
        $factura->updateQuietly(['status' => FacturaStatus::PENDING]);

        defer(fn () => $this->saveToDrive($factura));

        return redirect()->route('admin.facturas.index')->with('success', 'Factura reactivada correctamente.');
    }

    public function duplicate(Factura $factura)
    {
        $factura->load('lineas');

        $lastFactura = Factura::where('number', 'like', 'FV-%')
            ->orderByRaw('CAST(SUBSTRING(number, 4) AS UNSIGNED) DESC')
            ->first();

        $nextNum = 1;
        if ($lastFactura && preg_match('/^FV-(\d+)$/', $lastFactura->number, $matches)) {
            $nextNum = intval($matches[1]) + 1;
        }
        $number = sprintf("FV-%d", $nextNum);

        $defaultDueDate = strtotime('+' . \App\Models\Configuracion::get('default_vencimiento_dias', 30) . ' days');

        $nuevaFactura = Factura::create([
            'number' => $number,
            'client_id' => $factura->client_id,
            'proyecto_id' => $factura->proyecto_id,
            'date' => time(),
            'due_date' => $defaultDueDate,
            'status' => FacturaStatus::PENDING,
            'notes' => $factura->notes,
            'description' => $factura->description,
            'subtotal' => $factura->subtotal,
            'tax_amount' => $factura->tax_amount,
            'irpf_amount' => $factura->irpf_amount,
            'total' => $factura->total,
        ]);

        foreach ($factura->lineas as $linea) {
            $nuevaFactura->lineas()->create([
                'concepto' => $linea->concepto,
                'descripcion' => $linea->descripcion,
                'cantidad' => $linea->cantidad,
                'precio_unitario' => $linea->precio_unitario,
                'porcentaje_iva' => $linea->porcentaje_iva,
                'porcentaje_irpf' => $linea->porcentaje_irpf,
                'total_linea' => $linea->total_linea,
            ]);
        }

        defer(fn () => clone $nuevaFactura->loadMissing('cliente') && $this->saveToDrive($nuevaFactura));

        return redirect()->route('admin.facturas.edit', $nuevaFactura->id)->with('success', 'Factura duplicada correctamente. Revisa los datos antes de guardarla.');
    }

    private function syncLineas(Factura $factura, array $lineas)
    {
        $factura->lineas()->delete();

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

            $factura->lineas()->create([
                'concepto' => $linea['concepto'],
                'descripcion' => $linea['descripcion'] ?? null,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'porcentaje_iva' => $ivaPct,
                'porcentaje_irpf' => $irpfPct,
                'total_linea' => $lineaTotal,
            ]);
        }

        $factura->updateQuietly([
            'subtotal' => $subtotal,
            'tax_amount' => $tax_amount,
            'irpf_amount' => $irpf_amount,
            'total' => $subtotal + $tax_amount - $irpf_amount,
        ]);
    }

    public function exportPdf(Factura $factura, Request $request)
    {
        $safeDocNumber = str_replace(['/', '\\'], '-', $factura->number ?? $factura->id);
        $disposition = $request->has('download') ? 'attachment' : 'inline';

        if ($factura->google_drive_file_id) {
            try {
                $adapter = Storage::disk('google_facturas')->getAdapter();
                $service = $adapter->getService();
                $response = $service->files->get($factura->google_drive_file_id, ['alt' => 'media']);
                $content = $response->getBody()->getContents();

                return response($content)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', $disposition . '; filename="' . $safeDocNumber . '.pdf"');
            } catch (\Throwable $e) {
                \Log::warning('No se pudo traer el PDF original guardado en Drive. Generando fallback local: ' . $e->getMessage());
            }
        }

        $pdfOutput = $this->generatePdfBytes($factura);

        return response($pdfOutput)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition . '; filename="' . $safeDocNumber . '.pdf"');
    }

    public function sendPdfEmail(Factura $factura, Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string'
        ]);
        
        $pdfOutput = $this->generatePdfBytes($factura);

        Mail::to($request->email)->send(new FacturaPdfMail($factura, $pdfOutput, $request->message));

        return back()->with('success', 'Factura enviada por correo electrónico a ' . $request->email);
    }

    private function generatePdfBytes(Factura $factura)
    {
        $factura->load(['lineas', 'cliente']);
        
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

        // Usamos temp pdf.factura que clonaremos de presupuesto después
        $pdf = Pdf::loadView('pdf.factura', [
            'factura' => $factura,
            'logoBase64' => $logoBase64,
            'configList' => $config
        ]);
        return $pdf->output();
    }

    public function saveToDrive(Factura $factura)
    {
        try {
            $pdfBinary = $this->generatePdfBytes($factura);
            
            $year = date('Y', is_numeric($factura->date) ? $factura->date : strtotime($factura->date));
            $rootId = env('GOOGLE_DRIVE_FOLDER_ID_FACTURAS');
            
            $adapter = Storage::disk('google_facturas')->getAdapter();
            $service = $adapter->getService();
            $rootDriveId = env('GOOGLE_DRIVE_FOLDER_ID_FACTURAS');

            $yearFolderId = $this->findOrCreateFolder($service, $year, $rootDriveId);
            $ventasFolderId = $this->findOrCreateFolder($service, 'VENTAS', $yearFolderId);
            $month = is_numeric($factura->date) ? date('n', $factura->date) : date('n', strtotime($factura->date));
            $quarter = ceil($month / 3);
            $quarterFolderName = "{$quarter}tri";
            $folderId = $this->findOrCreateFolder($service, $quarterFolderName, $ventasFolderId);

            $factura->loadMissing('cliente');
            $clientName = $factura->cliente ? $factura->cliente->name : 'Cliente';
            // Limpiar caracteres no válidos para nombres de archivo en Drive si los hubiera en el nombre de cliente
            $safeClientName = str_replace(['/', '\\'], '-', $clientName);
            $safeDocNumber = str_replace(['/', '\\'], '-', $factura->number ?? $factura->id);
            $fileNameSuffix = $factura->status === FacturaStatus::CANCELED ? ' (Anulada)' : '';
            $fileName = "{$safeDocNumber} - {$safeClientName}{$fileNameSuffix}.pdf";

            if ($factura->google_drive_file_id) {
                try {
                    $file = $service->files->get($factura->google_drive_file_id, ['fields' => 'parents, trashed']);
                    if (!$file->getTrashed()) {
                        $previousParents = implode(',', $file->getParents());
                        $service->files->update($factura->google_drive_file_id, new DriveFile(['name' => $fileName]), [
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
            
            $factura->updateQuietly(['google_drive_file_id' => $fileId]);
            
        } catch (\Exception $e) {
            \Log::error('Fallo al subir factura nativa a Google Drive: ' . $e->getMessage());
        }
    }

    private function findOrCreateFolder($service, $name, $parentId)
    {
        $optParams = [
            'q' => "'$parentId' in parents and mimeType = 'application/vnd.google-apps.folder' and name = '$name' and trashed = false",
            'fields' => 'files(id)'
        ];
        $files = $service->files->listFiles($optParams)->getFiles();
        if (count($files) > 0) return $files[0]->getId();

        $folder = $service->files->create(new DriveFile([
            'name' => $name, 'mimeType' => 'application/vnd.google-apps.folder', 'parents' => [$parentId]
        ]), ['fields' => 'id']);
        
        return $folder->getId();
    }
}
