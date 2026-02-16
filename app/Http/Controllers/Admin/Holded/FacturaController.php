<?php

namespace App\Http\Controllers\Admin\Holded;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Services\HoldedService;
use Google\Service\Drive\DriveFile;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Factura;

class FacturaController extends Controller
{
    protected HoldedService $holdedService;

    public function __construct(HoldedService $holdedService)
    {
        $this->holdedService = $holdedService;
    }

    public function index(Request $request)
    {
        $month = now()->month;
        $year = now()->year;
        $quarter = ceil($month / 3);
        
        $defaultStart = sprintf('%04d-%02d-01', $year, ($quarter - 1) * 3 + 1);
        $defaultEnd = (new \DateTime(sprintf('%04d-%02d-01', $year, $quarter * 3)))->format('Y-m-t');

        $start = $request->input('start', $defaultStart);
        $end = $request->input('end', $defaultEnd);

        // Convert dates to timestamps for Holded API
        $startTimestamp = strtotime($start . ' 00:00:00');
        $endTimestamp = strtotime($end . ' 23:59:59');

        // Sync with Holded (updates local database)
        $syncResult = $this->holdedService->syncDocuments('invoice', [
            'starttmp' => $startTimestamp,
            'endtmp' => $endTimestamp,
        ]);

        // Fetch from local database with search and pagination
        $facturas = Factura::whereBetween('date', [$startTimestamp, $endTimestamp])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('contact_name', 'like', "%{$search}%")
                      ->orWhere('holded_id', 'like', "%{$search}%")
                      ->orWhere('raw_data->docNumber', 'like', "%{$search}%");
                });
            })
            ->when(request('status'), function ($query, $status) {
                if ($status === 'pagada') {
                    $query->where('raw_data->paymentsPending', 0);
                } elseif ($status === 'pendiente') {
                    $query->where('raw_data->paymentsTotal', 0);
                } elseif ($status === 'parcial') {
                    $query->where('raw_data->paymentsPending', '>', 0)
                          ->where('raw_data->paymentsTotal', '>', 0);
                }
            })
            ->orderBy('date', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Holded/Facturas/Index', [
            'facturas' => $facturas,
            'errorMessage' => $syncResult['error'] ?? null,
            'filters' => [
                'start' => $start,
                'end' => $end,
                'search' => $request->input('search'),
                'status' => $request->input('status'),
            ],
        ]);
    }

    public function downloadPdf(string $id)
    {
        $factura = Factura::where('holded_id', $id)->first();
        
        // Ensure file is in Drive and get content
        $fileContent = $this->ensureInDrive($factura, $id);

        if (!$fileContent) {
             abort(404, 'No se pudo recuperar el PDF.');
        }

        $docNumber = $factura->raw_data['docNumber'] ?? $id;
        $clientName = $factura->contact_name ?? 'Cliente';
        // Sanitize filename
        $safeDocNumber = str_replace(['/', '\\'], '-', $docNumber);
        $safeClientName = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $clientName);
        $fileName = "{$safeDocNumber} - {$safeClientName}.pdf";

        $disposition = request()->has('download') ? 'attachment' : 'inline';

        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition . '; filename="' . $fileName . '"');
    }

    public function ensureInDrive(?Factura $factura, string $holdedId)
    {
        // 1. Try to serve from Google Drive if we have the ID locally
        if ($factura && $factura->google_drive_file_id) {
            try {
                $adapter = Storage::disk('google_facturas')->getAdapter();
                $service = $adapter->getService();
                $response = $service->files->get($factura->google_drive_file_id, ['alt' => 'media']);
                return $response->getBody()->getContents();
            } catch (\Exception $e) {
                // Return null to trigger fallback, or maybe log
                // If 404, we should try to re-upload
            }
        }

        // 2. Fetch from Holded
        $pdfBase64 = $this->holdedService->getDocumentPdf('invoice', $holdedId);

        if (!$pdfBase64) {
            return null;
        }

        $pdfBinary = base64_decode($pdfBase64);

        // 3. Save to Google Drive if we have the record
        if ($factura) {
            try {
                $adapter = Storage::disk('google_facturas')->getAdapter();
                $service = $adapter->getService();
                $rootDriveId = env('GOOGLE_DRIVE_FOLDER_ID_FACTURAS');

                // Structure: {Year}/VENTAS/{Quarter}tri/{docNumber}.pdf
                
                // Step 1: Find or Create Year Folder
                $year = date('Y', $factura->date);
                $yearFolderId = $this->findOrCreateFolder($service, $year, $rootDriveId);

                // Step 2: Find or Create 'VENTAS' Folder
                $ventasFolderId = $this->findOrCreateFolder($service, 'VENTAS', $yearFolderId);

                // Step 3: Find or Create Quarter Folder (1tri, 2tri...)
                $month = date('n', $factura->date);
                $quarter = ceil($month / 3);
                $quarterFolderName = "{$quarter}tri";
                $quarterFolderId = $this->findOrCreateFolder($service, $quarterFolderName, $ventasFolderId);

                // Step 4: Save File
                if ($quarterFolderId) {
                    $docNumber = $factura->raw_data['docNumber'] ?? $holdedId;
                    $clientName = $factura->contact_name ?? 'Cliente';
                    
                    $safeDocNumber = str_replace(['/', '\\'], '-', $docNumber);
                    $safeClientName = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $clientName);
                    $fileName = "{$safeDocNumber} - {$safeClientName}.pdf";

                    // Check if file already exists
                    $fileOptParams = [
                        'q' => "'$quarterFolderId' in parents and name = '$fileName' and trashed = false",
                        'fields' => 'files(id)'
                    ];
                    $existingFiles = $service->files->listFiles($fileOptParams)->getFiles();

                    if (count($existingFiles) > 0) {
                        $fileId = $existingFiles[0]->getId();
                    } else {
                        // Upload file
                        $fileMeta = new DriveFile([
                            'name' => $fileName,
                            'parents' => [$quarterFolderId]
                        ]);
                        
                        $file = $service->files->create($fileMeta, [
                            'data' => $pdfBinary,
                            'mimeType' => 'application/pdf',
                            'uploadType' => 'multipart',
                            'fields' => 'id'
                        ]);
                        
                        $fileId = $file->getId();
                    }
                    
                    $factura->update(['google_drive_file_id' => $fileId]);
                }

            } catch (\Exception $e) {
                 \Log::error('Google Drive Upload Failed: ' . $e->getMessage());
            }
        }

        return $pdfBinary;
    }

    public function syncDrive(Request $request)
    {
        try {
            // Increase time limit for this request
            set_time_limit(300);

            $year = date('Y');
            
            // We can call the command, but since we want fine-grained feedback 
            // and the command is designed for CLI, we'll replicate the core loop here 
            // or better, Refactor the command to use a service.
            // For now, to avoid "spaghetti" and N+1 in controller, let's call the command 
            // and capture output if possible, OR just run the logic cleanly.
            // Since I previously said "no spaghetti", let's be clean.
            
            // Re-using the logic from the command is best done by extracting it to a service.
            // However, for this task, I will implement the loop here using the same methods,
            // effectively "Controller as Service" for this action, or keep it in the command.
            
            // Let's use Artisan::call for simplicity and robustness (it runs the same tested logic).
            $exitCode = \Artisan::call('holded:drive-sync-facturas', ['year' => $year]);
            $output = \Artisan::output();

            if ($exitCode === 0) {
                $stats = [];
                if (preg_match('/JSON_RESULT=(.*)/', $output, $matches)) {
                    $json = $matches[1];
                    $stats = json_decode($json, true) ?? [];
                }

                $message = 'Sincronización con Drive completada.<br>';
                if (!empty($stats)) {
                    $message .= "Facturas recuperadas: {$stats['synced']}<br>" .
                                "Procesadas: {$stats['processed']}<br>" .
                                "Subidas a Drive: {$stats['uploaded']}<br>" .
                                "Ya existentes: {$stats['skipped']}<br>" .
                                "Errores: {$stats['errors']}";
                } else {
                    $message .= " Revisa el log para detalles.";
                }

                return back()->with('success', $message);
            } else {
                return back()->with('error', 'Hubo un error al sincronizar con Drive.');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error crítico: ' . $e->getMessage());
        }
    }

    private function findOrCreateFolder($service, $folderName, $parentId)
    {
        if (!$parentId) {
             // Fallback if no root ID, though it should be configured
             throw new \Exception("Parent ID missing for folder creation");
        }

        $optParams = [
            'q' => "'$parentId' in parents and mimeType = 'application/vnd.google-apps.folder' and name = '$folderName' and trashed = false",
            'fields' => 'files(id)'
        ];
        $results = $service->files->listFiles($optParams);
        $files = $results->getFiles();

        if (count($files) > 0) {
            return $files[0]->getId();
        }

        // Create folder
        $folderMeta = new DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId]
        ]);
        $folder = $service->files->create($folderMeta, ['fields' => 'id']);
        
        return $folder->getId();
    }
}
