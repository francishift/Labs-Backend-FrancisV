<?php

namespace App\Services;

use App\Models\PurchaseFactura;
use App\Enums\PurchaseFacturaStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Exception;
use DateTime;

class PurchaseFacturaService
{
    private GeminiInvoiceService $geminiService;
    private GoogleDriveDocumentService $googleDriveService;

    public function __construct(
        GeminiInvoiceService $geminiService,
        GoogleDriveDocumentService $googleDriveService
    ) {
        $this->geminiService = $geminiService;
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Procesa un PDF subido: sube a Drive y extrae datos con IA.
     */
    public function procesarFacturaDesdePdf(UploadedFile $file): array
    {
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

        try {
            // 2. Subida a Drive
            $driveFileId = $this->subirDocumentoADrive($fileName, $pdfBinary, now());
            if (!$driveFileId) {
                throw new Exception("Error al subir el archivo a Google Drive.");
            }
            $factura->update(['google_drive_file_id' => $driveFileId]);
            
            // 3. Extracción de Datos
            $extractedData = $this->geminiService->extractInvoiceData($pdfBinary);

            if (empty($extractedData)) {
                $factura->update(['status' => 'error_ia', 'provider_name' => 'Error en extracción IA']);
                return [
                    'success' => true, 
                    'factura' => $factura, 
                    'message' => 'IA falló al extraer datos.'
                ];
            }

            // 4. Manejar duplicados y actualizar
            return $this->manejarDatosExtraidos($factura, $extractedData);

        } catch (Exception $e) {
            Log::error('Purchase Invoice Error: ' . $e->getMessage());
            
            // Usar DB directamente para evitar problemas de modelo sucio con restricciones de unicidad
            DB::table('purchase_facturas')
                ->where('id', $factura->id)
                ->update([
                    'status' => 'error',
                    'notes' => 'Error: ' . $e->getMessage(),
                    'updated_at' => now(),
                ]);
                
            return [
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Resuelve un duplicado sobrescribiendo el original.
     */
    public function resolverDuplicado(PurchaseFactura $factura): void
    {
        if ($factura->status !== PurchaseFacturaStatus::DUPLICADA) return;
        
        $duplicateOfId = $factura->raw_data['duplicate_of'] ?? null;
        if (!$duplicateOfId) return;

        DB::transaction(function () use ($factura, $duplicateOfId) {
            if ($original = PurchaseFactura::find($duplicateOfId)) {
                if ($original->google_drive_file_id) {
                    $this->googleDriveService->deleteDocument('google_facturas', $original->google_drive_file_id);
                }
                $original->forceDelete(); 
            }

            $factura->update([
                'number' => $factura->raw_data['intended_number'] ?? $factura->number,
                'status' => 'recibida',
            ]);

            if ($factura->date) {
                $this->moverCarpetaDriveSiEsNecesario($factura, new DateTime($factura->date->format('Y-m-d')));
            }
        });
    }

    /**
     * Actualiza manualmente una factura.
     */
    public function actualizarFactura(PurchaseFactura $factura, array $datosValidados): PurchaseFactura
    {
        $factura->update($datosValidados);
        return $factura;
    }

    /**
     * Elimina una factura y su archivo en Google Drive.
     */
    public function eliminarFactura(PurchaseFactura $factura): void
    {
        if ($factura->google_drive_file_id) {
            $this->googleDriveService->deleteDocument('google_facturas', $factura->google_drive_file_id);
        }
        $factura->delete();
    }

    /**
     * Maneja la lógica de duplicados y actualiza el registro.
     */
    private function manejarDatosExtraidos(PurchaseFactura $factura, array $data): array
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
                return ['success' => true, 'factura' => $factura, 'message' => 'Factura duplicada detectada.'];
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

        if (!empty($data['invoice_date'])) {
            $extractedDate = new DateTime($data['invoice_date']);
            $updateData['date'] = $extractedDate;
            if ($extractedDate->format('Y-m') !== now()->format('Y-m')) {
                $this->moverCarpetaDriveSiEsNecesario($factura, $extractedDate);
            }
        }

        $factura->update($updateData);

        return ['success' => true, 'factura' => $factura, 'message' => "Factura {$factura->number} procesada."];
    }

    /**
     * Sube documento y genera la ruta correcta basada en la fecha.
     */
    private function subirDocumentoADrive(string $fileName, string $pdfBinary, \DateTimeInterface $date): ?string
    {
        $rootId = env('GOOGLE_DRIVE_FOLDER_ID_FACTURAS');
        $quarter = ceil($date->format('n') / 3);
        
        $pathCarpetas = [
            $date->format('Y'),
            'COMPRAS',
            "{$quarter}tri"
        ];

        return $this->googleDriveService->uploadDocument(
            'google_facturas',
            $rootId,
            $pathCarpetas,
            $fileName,
            $pdfBinary
        );
    }

    /**
     * Mueve el documento si la fecha extraída pertenece a otra carpeta.
     */
    private function moverCarpetaDriveSiEsNecesario(PurchaseFactura $factura, DateTime $date): void
    {
        if (!$fileId = $factura->google_drive_file_id) return;
        
        $rootId = env('GOOGLE_DRIVE_FOLDER_ID_FACTURAS');
        $quarter = ceil($date->format('n') / 3);
        
        $pathCarpetas = [
            $date->format('Y'),
            'COMPRAS',
            "{$quarter}tri"
        ];

        $this->googleDriveService->moveDocument(
            'google_facturas',
            $fileId,
            $rootId,
            $pathCarpetas
        );
    }
}
