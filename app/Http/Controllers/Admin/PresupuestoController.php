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

class PresupuestoController extends Controller
{
    public function index(Request $request)
    {
        $presupuestos = Presupuesto::with('cliente')
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('contact_name', 'like', "%{$search}%")
                      ->orWhere('number', 'like', "%{$search}%");
                });
            })
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Presupuestos/Index', [
            'presupuestos' => $presupuestos,
            'filters' => ['search' => $request->input('search')],
        ]);
    }

    public function create()
    {
        $clientes = Client::orderBy('name')->get(['id', 'contact', 'name', 'cif_nif', 'email']);
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
            'contact_name' => 'required|string',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'lineas' => 'required|array|min:1',
            'lineas.*.concepto' => 'required|string',
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
            'contact_name' => $request->contact_name,
            'date' => strtotime($request->date),
            'due_date' => $request->due_date ? date('Y-m-d', strtotime($request->due_date)) : null,
            'status' => 0,
            'notes' => $request->notes,
            'description' => $request->description,
        ]);

        $this->syncLineas($presupuesto, $request->lineas);

        $this->saveToDrive($presupuesto);

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
        $clientes = Client::orderBy('name')->get(['id', 'contact', 'name', 'cif_nif', 'email']);
        
        return Inertia::render('Admin/Presupuestos/Edit', [
            'presupuesto' => $presupuesto,
            'clientes' => $clientes,
            'defaultIva' => (float) Configuracion::get('default_iva', 21),
            'defaultIrpf' => (float) Configuracion::get('default_irpf', 0),
        ]);
    }

    public function update(Request $request, Presupuesto $presupuesto)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'contact_name' => 'required|string',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'lineas' => 'required|array|min:1',
            'lineas.*.concepto' => 'required|string',
            'lineas.*.cantidad' => 'required|numeric|min:0.01',
            'lineas.*.precio_unitario' => 'required|numeric',
            'lineas.*.porcentaje_iva' => 'required|numeric|min:0',
            'lineas.*.porcentaje_irpf' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $presupuesto->update([
            'client_id' => $request->client_id,
            'contact_name' => $request->contact_name,
            'date' => strtotime($request->date),
            'due_date' => $request->due_date ? date('Y-m-d', strtotime($request->due_date)) : null,
            'notes' => $request->notes,
            'description' => $request->description,
        ]);

        $this->syncLineas($presupuesto, $request->lineas);

        $this->saveToDrive($presupuesto);

        return redirect()->route('admin.presupuestos.index')->with('success', 'Presupuesto actualizado con éxito.');
    }

    public function destroy(Presupuesto $presupuesto)
    {
        $presupuesto->delete();
        return redirect()->route('admin.presupuestos.index')->with('success', 'Presupuesto eliminado.');
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
                    ->header('Content-Disposition', $disposition . '; filename="' . $safeDocNumber . '.pdf"');
            } catch (\Exception $e) {
                \Log::warning('No se pudo traer el PDF original guardado en Drive. Generando fallback local: ' . $e->getMessage());
            }
        }

        $pdfOutput = $this->generatePdfBytes($presupuesto);

        return response($pdfOutput)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition . '; filename="' . $safeDocNumber . '.pdf"');
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

    private function saveToDrive(Presupuesto $presupuesto)
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

            $safeDocNumber = str_replace(['/', '\\'], '-', $presupuesto->number ?? $presupuesto->id);
            $fileName = "{$safeDocNumber}.pdf";

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
            
        } catch (\Exception $e) {
            \Log::error('Fallo al subir presupuesto nativo a Google Drive: ' . $e->getMessage());
        }
    }
}
