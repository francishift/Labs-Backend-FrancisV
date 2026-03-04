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

        // Convertir fechas a timestamps para la API de Holded
        $startTimestamp = strtotime($start . ' 00:00:00');
        $endTimestamp = strtotime($end . ' 23:59:59');

        // Sincronizar con Holded (actualiza la base de datos local)
        $syncResult = $this->holdedService->syncDocuments('invoice', [
            'starttmp' => $startTimestamp,
            'endtmp' => $endTimestamp,
        ]);

        // Obtener de la base de datos local con búsqueda y paginación
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
            ->when(request('client'), function ($query, $client) {
                $query->where('contact_name', $client);
            })
            ->orderBy('date', 'desc')
            ->paginate(10)
            ->withQueryString();

        $clients = Factura::select('contact_name')
            ->distinct()
            ->whereNotNull('contact_name')
            ->where('contact_name', '!=', '')
            ->orderBy('contact_name')
            ->pluck('contact_name');

        return Inertia::render('Admin/Holded/Facturas/Index', [
            'facturas' => $facturas,
            'clients' => $clients,
            'errorMessage' => $syncResult['error'] ?? null,
            'filters' => [
                'start' => $start,
                'end' => $end,
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'client' => $request->input('client'),
            ],
        ]);
    }

    public function downloadPdf(string $id)
    {
        $factura = Factura::where('holded_id', $id)->first();
        
        // Asegurar que el archivo está en Drive y obtener el contenido
        $fileContent = $this->ensureInDrive($factura, $id);

        if (!$fileContent) {
             abort(404, 'No se pudo recuperar el PDF.');
        }

        $docNumber = $factura->raw_data['docNumber'] ?? $id;
        $clientName = $factura->contact_name ?? 'Cliente';
        // Sanear el nombre del archivo
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
        // 1. Intentar servir desde Google Drive si tenemos el ID localmente
        if ($factura && $factura->google_drive_file_id) {
            try {
                $adapter = Storage::disk('google_facturas')->getAdapter();
                $service = $adapter->getService();
                $response = $service->files->get($factura->google_drive_file_id, ['alt' => 'media']);
                return $response->getBody()->getContents();
            } catch (\Exception $e) {
                // Devolver null para activar el fallback, o tal vez registrar en log
                // Si es 404, deberíamos intentar re-subir
            }
        }

        // 2. Obtener de Holded
        $pdfBase64 = $this->holdedService->getDocumentPdf('invoice', $holdedId);

        if (!$pdfBase64) {
            return null;
        }

        $pdfBinary = base64_decode($pdfBase64);

        // 3. Guardar en Google Drive si tenemos el registro
        if ($factura) {
            try {
                $adapter = Storage::disk('google_facturas')->getAdapter();
                $service = $adapter->getService();
                $rootDriveId = env('GOOGLE_DRIVE_FOLDER_ID_FACTURAS');

                // Estructura: {Año}/VENTAS/{Trimestre}tri/{docNumber}.pdf
                
                // Paso 1: Buscar o Crear Carpeta del Año
                $year = date('Y', $factura->date);
                $yearFolderId = $this->findOrCreateFolder($service, $year, $rootDriveId);

                // Paso 2: Buscar o Crear Carpeta 'VENTAS'
                $ventasFolderId = $this->findOrCreateFolder($service, 'VENTAS', $yearFolderId);

                // Paso 3: Buscar o Crear Carpeta del Trimestre (1tri, 2tri...)
                $month = date('n', $factura->date);
                $quarter = ceil($month / 3);
                $quarterFolderName = "{$quarter}tri";
                $quarterFolderId = $this->findOrCreateFolder($service, $quarterFolderName, $ventasFolderId);

                // Paso 4: Guardar Archivo
                if ($quarterFolderId) {
                    $docNumber = $factura->raw_data['docNumber'] ?? $holdedId;
                    $clientName = $factura->contact_name ?? 'Cliente';
                    
                    $safeDocNumber = str_replace(['/', '\\'], '-', $docNumber);
                    $safeClientName = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $clientName);
                    $fileName = "{$safeDocNumber} - {$safeClientName}.pdf";

                    // Comprobar si el archivo ya existe
                    $fileOptParams = [
                        'q' => "'$quarterFolderId' in parents and name = '$fileName' and trashed = false",
                        'fields' => 'files(id)'
                    ];
                    $existingFiles = $service->files->listFiles($fileOptParams)->getFiles();

                    if (count($existingFiles) > 0) {
                        $fileId = $existingFiles[0]->getId();
                    } else {
                        // Subir archivo
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
                 \Log::error('Fallo al subir a Google Drive: ' . $e->getMessage());
            }
        }

        return $pdfBinary;
    }

    public function syncDrive(Request $request)
    {
        try {
            // Aumentar el límite de tiempo para esta petición
            set_time_limit(300);

            $year = date('Y');
            
            // Podemos llamar al comando, pero como queremos feedback detallado
            // y el comando está diseñado para CLI, replicaremos el bucle principal aquí
            // o mejor, refactorizar el comando para usar un servicio.
            // Por ahora, para evitar "espagueti" y N+1 en el controlador, llamemos al comando
            // y capturemos la salida si es posible, O simplemente ejecutemos la lógica limpiamente.
            // Como dije anteriormente "nada de espagueti", seamos limpios.
            
            // Reutilizar la lógica del comando se hace mejor extrayéndola a un servicio.
            // Sin embargo, para esta tarea, implementaré el bucle aquí usando los mismos métodos,
            // efectivamente "Controlador como Servicio" para esta acción, o mantenerlo en el comando.
            
            // Usemos Artisan::call por simplicidad y robustez (ejecuta la misma lógica probada).
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
             // Fallback si no hay ID raíz, aunque debería estar configurado
             throw new \Exception("Falta el ID del padre para la creación de la carpeta");
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

        // Crear carpeta
        $folderMeta = new DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId]
        ]);
        $folder = $service->files->create($folderMeta, ['fields' => 'id']);
        
        return $folder->getId();
    }
}
