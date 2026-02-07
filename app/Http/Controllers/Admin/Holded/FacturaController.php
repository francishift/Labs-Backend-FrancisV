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
        $start = $request->input('start', '2025-01-01');
        $end = $request->input('end', date('Y-m-d'));

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
            ],
        ]);
    }

    public function downloadPdf(string $id)
    {
        $factura = Factura::where('holded_id', $id)->first();
        
        // 1. Try to serve from Google Drive if we have the ID locally
        if ($factura && $factura->google_drive_file_id) {
            try {
                // We use the 'google' disk which points to the root/company folder
                $adapter = Storage::disk('google')->getAdapter();
                $service = $adapter->getService();
                $response = $service->files->get($factura->google_drive_file_id, ['alt' => 'media']);
                $fileContent = $response->getBody()->getContents();

                $docNumber = $factura->raw_data['docNumber'] ?? $id;
                $safeDocNumber = str_replace(['/', '\\'], '-', $docNumber);
                $disposition = request()->has('download') ? 'attachment' : 'inline';

                return response($fileContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', $disposition . '; filename="' . $safeDocNumber . '.pdf"');
            } catch (\Exception $e) {
                // If failed, fall back to Holded
            }
        }

        // 2. Fetch from Holded
        $pdfBase64 = $this->holdedService->getDocumentPdf('invoice', $id);

        if (!$pdfBase64) {
            return back()->with('error', 'No se pudo recuperar el PDF de Holded.');
        }

        $pdfBinary = base64_decode($pdfBase64);

        // 3. Save to Google Drive if we have the record
        if ($factura) {
            try {
                $adapter = Storage::disk('google_facturas')->getAdapter();
                $service = $adapter->getService();
                $rootDriveId = env('GOOGLE_DRIVE_FOLDER_ID_FACTURAS');

                // Structure: {Year}/VENTAS/{Quarter}tri/{docNumber}.pdf
                // Note: The disk is already rooted at 'FACTURAS', so we start with Year.
                
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
                    $docNumber = $factura->raw_data['docNumber'] ?? $id;
                    $safeDocNumber = str_replace(['/', '\\'], '-', $docNumber);
                    $fileName = "{$safeDocNumber}.pdf";

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
                // Log error but verify functionality
                 \Log::error('Google Drive Upload Failed: ' . $e->getMessage());
            }
        }

        $docNumber = $factura->raw_data['docNumber'] ?? $id;
        $safeDocNumber = str_replace(['/', '\\'], '-', $docNumber);
        $disposition = request()->has('download') ? 'attachment' : 'inline';

        return response($pdfBinary)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition . '; filename="' . $safeDocNumber . '.pdf"');
    }

    private function findOrCreateFolder($service, $folderName, $parentId)
    {
        if (!$parentId) {
             // Fallback if no root ID, though it should be configured
             throw new \Exception("Parent ID missing for folder creation");
        }

        $optParams = [
            'q' => "'$parentId' in parents and mimeType = 'application/vnd.google-apps.folder' and name = '$folderName' and trashed = false",
            'fields' => 'files(id, name)'
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
