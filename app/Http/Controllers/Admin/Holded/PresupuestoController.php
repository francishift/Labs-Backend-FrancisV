<?php

namespace App\Http\Controllers\Admin\Holded;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Services\HoldedService;
use Google\Service\Drive\DriveFile;
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

        // Fetch from local database with search and pagination
        $presupuestos = Presupuesto::whereBetween('date', [$startTimestamp, $endTimestamp])
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

        return Inertia::render('Admin/Holded/Presupuestos/Index', [
            'presupuestos' => $presupuestos,
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
        $presupuesto = Presupuesto::where('holded_id', $id)->first();
        
        // 1. Try to serve from Google Drive if we have the ID locally
        if ($presupuesto && $presupuesto->google_drive_file_id) {
            try {
                $adapter = Storage::disk('google_presupuestos')->getAdapter();
                $service = $adapter->getService();
                $response = $service->files->get($presupuesto->google_drive_file_id, ['alt' => 'media']);
                $fileContent = $response->getBody()->getContents();

                $docNumber = $presupuesto->raw_data['docNumber'] ?? $id;
                $safeDocNumber = str_replace(['/', '\\'], '-', $docNumber);
                $disposition = request()->has('download') ? 'attachment' : 'inline';

                return response($fileContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', $disposition . '; filename="' . $safeDocNumber . '.pdf"');
            } catch (\Exception $e) {
                // Check if it's a 404 or other error, log it, and continue to Holded fallback
                // \Log::warning("Failed to retrieve from Drive ID {$presupuesto->google_drive_file_id}: " . $e->getMessage());
            }
        }

        // 2. Fetch from Holded
        $pdfBase64 = $this->holdedService->getDocumentPdf('estimate', $id);

        if (!$pdfBase64) {
            return back()->with('error', 'No se pudo recuperar el PDF de Holded.');
        }

        $pdfBinary = base64_decode($pdfBase64);

        // 3. Save to Google Drive if we have the budget record
        if ($presupuesto) {
            $year = date('Y', $presupuesto->date);
            $rootId = env('GOOGLE_DRIVE_FOLDER_ID_PRESUPUESTOS');
            
            try {
                $adapter = Storage::disk('google_presupuestos')->getAdapter();
                $service = $adapter->getService();

                // Check for year folder
                $optParams = [
                    'q' => "'$rootId' in parents and mimeType = 'application/vnd.google-apps.folder' and name = '$year' and trashed = false",
                    'fields' => 'files(id, name)'
                ];
                $results = $service->files->listFiles($optParams);
                $files = $results->getFiles();

                $folderId = null;
                if (count($files) > 0) {
                    $folderId = $files[0]->getId();
                } else {
                    // Create folder
                    $folderMeta = new DriveFile([
                        'name' => $year,
                        'mimeType' => 'application/vnd.google-apps.folder',
                        'parents' => [$rootId]
                    ]);
                    $folder = $service->files->create($folderMeta, ['fields' => 'id']);
                    $folderId = $folder->getId();
                }

                if ($folderId) {
                    $docNumber = $presupuesto->raw_data['docNumber'] ?? $id;
                    $safeDocNumber = str_replace(['/', '\\'], '-', $docNumber);
                    $fileName = "{$safeDocNumber}.pdf";

                    // Check if file already exists in the folder
                    $fileOptParams = [
                        'q' => "'$folderId' in parents and name = '$fileName' and trashed = false",
                        'fields' => 'files(id)'
                    ];
                    $existingFiles = $service->files->listFiles($fileOptParams)->getFiles();

                    if (count($existingFiles) > 0) {
                        $fileId = $existingFiles[0]->getId();
                    } else {
                        // Upload file
                        $fileMeta = new DriveFile([
                            'name' => $fileName,
                            'parents' => [$folderId]
                        ]);
                        
                        $file = $service->files->create($fileMeta, [
                            'data' => $pdfBinary,
                            'mimeType' => 'application/pdf',
                            'uploadType' => 'multipart',
                            'fields' => 'id'
                        ]);
                        
                        $fileId = $file->getId();
                    }
                    
                    $presupuesto->update(['google_drive_file_id' => $fileId]);
                }
            } catch (\Exception $e) {
                // Log error but verify functionality
                // \Log::error('Google Drive Upload Failed: ' . $e->getMessage());
            }
        }

        $docNumber = $presupuesto->raw_data['docNumber'] ?? $id;
        $safeDocNumber = str_replace(['/', '\\'], '-', $docNumber);

        $disposition = request()->has('download') ? 'attachment' : 'inline';

        return response($pdfBinary)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition . '; filename="' . $safeDocNumber . '.pdf"');
    }
}
