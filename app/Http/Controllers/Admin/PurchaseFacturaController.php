<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseFactura;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Google\Service\Drive\DriveFile;
use App\Services\HoldedApiService;
use App\Services\GeminiInvoiceService;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleDriveService; // Added this line

class PurchaseFacturaController extends Controller
{
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

        // Filtros de Fecha
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

        // 4. Ordenación
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

    public function store(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf',
                function ($attribute, $value, $fail) {
                    if ($value->getSize() > 10485760) {
                        $fail('El archivo no debe pesar más de 10 MB.');
                    }
                },
            ],
        ]);

        try {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $pdfBinary = file_get_contents($file->getRealPath());

            // 1. Crear registro inicial
            $factura = PurchaseFactura::create([
                'number' => 'PENDING-' . time() . '-' . uniqid(),
                'provider_name' => 'Pendiente de procesar',
                'date' => now(),
                'total' => 0,
                'status' => 'procesando',
            ]);

            // 2. Subida a Drive
            $driveFileId = $this->uploadToDrive($factura, $pdfBinary, $fileName, now());
            if (!$driveFileId) throw new \Exception("Error al subir el archivo a Google Drive.");
            $factura->update(['google_drive_file_id' => $driveFileId]);
            
            // 3. Extracción de Datos
            $geminiService = new \App\Services\GeminiInvoiceService();
            $extractedData = $geminiService->extractInvoiceData($pdfBinary);

            if (empty($extractedData)) {
                $factura->update(['status' => 'error_ia', 'provider_name' => 'Error en extracción IA']);
                return response()->json(['success' => true, 'factura' => $factura, 'message' => 'IA falló al extraer datos.']);
            }

            // 4. Manejar duplicados y actualizar
            return $this->handleExtractedData($factura, $extractedData);

        } catch (\Exception $e) {
            \Log::error('Purchase Invoice Error: ' . $e->getMessage());
            if (isset($factura)) {
                // Usar DB directamente para evitar problemas de modelo sucio con restricciones de unicidad
                \DB::table('purchase_facturas')
                    ->where('id', $factura->id)
                    ->update([
                        'status' => 'error',
                        'notes' => 'Error: ' . $e->getMessage(),
                        'updated_at' => now(),
                    ]);
            }
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    private function handleExtractedData(PurchaseFactura $factura, array $data)
    {
        $newNumber = $data['invoice_id'] ?? $factura->number;
        
        $existing = PurchaseFactura::withTrashed()
            ->where('number', $newNumber)
            ->where('id', '!=', $factura->id)
            ->first();
            
        if ($existing) {
            if ($existing->trashed()) {
                $existing->forceDelete();
            } else {
                // Duplicado encontrado: marcar y detener
                $factura->update([
                    'number' => 'DUP-' . time() . '-' . $newNumber,
                    'provider_name' => $data['supplier_name'] ?? $factura->provider_name,
                    'total' => $data['total_amount'] ?? 0,
                    'net_amount' => $data['net_amount'] ?? 0,
                    'tax_amount' => $data['tax_amount'] ?? 0,
                    'irpf_amount' => $data['irpf_amount'] ?? 0,
                    'raw_data' => array_merge($data['raw'] ?? [], ['duplicate_of' => $existing->id, 'intended_number' => $newNumber]),
                    'status' => 'duplicada',
                ]);
                return response()->json(['success' => true, 'factura' => $factura, 'message' => 'Factura duplicada detectada.']);
            }
        }

        $updateData = [
            'number' => $newNumber,
            'provider_name' => $data['supplier_name'] ?? $factura->provider_name,
            'total' => $data['total_amount'] ?? 0,
            'net_amount' => $data['net_amount'] ?? 0,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'irpf_amount' => $data['irpf_amount'] ?? 0,
            'raw_data' => $data['raw'] ?? [],
            'status' => 'recibida',
        ];

        if ($data['invoice_date']) {
            $extractedDate = new \DateTime($data['invoice_date']);
            $updateData['date'] = $extractedDate;
            if ($extractedDate->format('Y-m') !== now()->format('Y-m')) {
                $this->moveToCorrectFolder($factura, $extractedDate);
            }
        }

        $factura->update($updateData);

        return response()->json(['success' => true, 'factura' => $factura, 'message' => "Factura {$factura->number} procesada."]);
    }

    private function uploadToDrive(PurchaseFactura $factura, $pdfBinary, $originalName, $date = null)
    {
        $date = $date ?: now();
        $adapter = Storage::disk('google_facturas')->getAdapter();
        $service = $adapter->getService();
        $rootDriveId = env('GOOGLE_DRIVE_FOLDER_ID_FACTURAS');

        $yearFolderId = $this->findOrCreateFolder($service, $date->format('Y'), $rootDriveId);
        $comprasFolderId = $this->findOrCreateFolder($service, 'COMPRAS', $yearFolderId);
        $quarter = ceil($date->format('n') / 3);
        $quarterFolderId = $this->findOrCreateFolder($service, "{$quarter}tri", $comprasFolderId);

        // Sobrescribir si existe
        $optParams = ['q' => "'$quarterFolderId' in parents and name = '$originalName' and trashed = false", 'fields' => 'files(id)'];
        $existingFiles = $service->files->listFiles($optParams)->getFiles();

        if (count($existingFiles) > 0) {
            $existingFileId = $existingFiles[0]->getId();
            $service->files->update($existingFileId, new DriveFile(), [
                'data' => $pdfBinary, 'mimeType' => 'application/pdf', 'uploadType' => 'multipart', 'fields' => 'id'
            ]);
            return $existingFileId;
        }

        $driveFile = $service->files->create(new DriveFile(['name' => $originalName, 'parents' => [$quarterFolderId]]), [
            'data' => $pdfBinary, 'mimeType' => 'application/pdf', 'uploadType' => 'multipart', 'fields' => 'id'
        ]);

        return $driveFile->getId();
    }

    private function moveToCorrectFolder(PurchaseFactura $factura, \DateTime $date)
    {
        try {
            if (!$fileId = $factura->google_drive_file_id) return;
            $adapter = Storage::disk('google_facturas')->getAdapter();
            $service = $adapter->getService();
            $rootDriveId = env('GOOGLE_DRIVE_FOLDER_ID_FACTURAS');

            $yearFolderId = $this->findOrCreateFolder($service, $date->format('Y'), $rootDriveId);
            $comprasFolderId = $this->findOrCreateFolder($service, 'COMPRAS', $yearFolderId);
            $quarter = ceil($date->format('n') / 3);
            $quarterFolderId = $this->findOrCreateFolder($service, "{$quarter}tri", $comprasFolderId);

            $file = $service->files->get($fileId, ['fields' => 'parents']);
            $previousParents = implode(',', $file->getParents());

            $service->files->update($fileId, new DriveFile(), [
                'addParents' => $quarterFolderId, 'removeParents' => $previousParents, 'fields' => 'id, parents'
            ]);
        } catch (\Exception $e) {
            \Log::error('Move file error: ' . $e->getMessage());
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

    public function update(Request $request, PurchaseFactura $purchaseFactura)
    {
        $validated = $request->validate([
            'number' => 'required|string|unique:purchase_facturas,number,' . $purchaseFactura->id,
            'provider_name' => 'required|string',
            'date' => 'required|date',
            'total' => 'required|numeric',
            'net_amount' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'irpf_amount' => 'nullable|numeric',
            'status' => 'required|string',
            'notes' => 'nullable|string',
        ]);
        $purchaseFactura->update($validated);
        return redirect()->back()->with('success', 'Factura de compra actualizada.');
    }

    public function destroy(PurchaseFactura $purchaseFactura)
    {
        if ($purchaseFactura->google_drive_file_id) {
            try {
                $service = Storage::disk('google_facturas')->getAdapter()->getService();
                $service->files->delete($purchaseFactura->google_drive_file_id);
            } catch (\Exception $e) {
                \Log::warning('Note: Could not delete from Drive: ' . $purchaseFactura->google_drive_file_id);
            }
        }
        $purchaseFactura->delete();
        return redirect()->back()->with('success', 'Factura eliminada.');
    }

    public function showPdf(PurchaseFactura $purchaseFactura)
    {
        if (!$purchaseFactura->google_drive_file_id) abort(404);
        try {
            $service = Storage::disk('google_facturas')->getAdapter()->getService();
            $response = $service->files->get($purchaseFactura->google_drive_file_id, ['alt' => 'media']);
            return response($response->getBody()->getContents())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $purchaseFactura->number . '.pdf"');
        } catch (\Exception $e) {
            abort(500);
        }
    }

    public function confirmOverwrite(PurchaseFactura $purchaseFactura)
    {
        if ($purchaseFactura->status !== 'duplicada') return redirect()->back();
        $duplicateOfId = $purchaseFactura->raw_data['duplicate_of'] ?? null;
        if (!$duplicateOfId) return redirect()->back();

        if ($original = PurchaseFactura::find($duplicateOfId)) {
            if ($original->google_drive_file_id) {
                try {
                    Storage::disk('google_facturas')->getAdapter()->getService()->files->delete($original->google_drive_file_id);
                } catch (\Exception $e) {}
            }
            $original->forceDelete(); 
        }

        $purchaseFactura->update([
            'number' => $purchaseFactura->raw_data['intended_number'] ?? $purchaseFactura->number,
            'status' => 'recibida',
        ]);

        if ($purchaseFactura->date) {
            $this->moveToCorrectFolder($purchaseFactura, new \DateTime($purchaseFactura->date->format('Y-m-d')));
        }

        return redirect()->back()->with('success', "Factura corregida.");
    }
}
