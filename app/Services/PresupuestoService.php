<?php

namespace App\Services;

use App\Models\Presupuesto;
use App\Enums\PresupuestoStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

class PresupuestoService
{
    private GoogleDriveDocumentService $googleDriveService;
    private DocumentPdfService $pdfService;

    public function __construct(
        GoogleDriveDocumentService $googleDriveService,
        DocumentPdfService $pdfService
    ) {
        $this->googleDriveService = $googleDriveService;
        $this->pdfService = $pdfService;
    }

    /**
     * Crea un nuevo presupuesto junto con sus líneas dentro de una transacción.
     */
    public function crearPresupuesto(array $datosValidados): Presupuesto
    {
        return DB::transaction(function () use ($datosValidados) {
            $lastPresupuesto = Presupuesto::where('number', 'like', 'PR-%')
                ->orderByRaw('CAST(SUBSTRING(number, 4) AS UNSIGNED) DESC')
                ->lockForUpdate()
                ->first();

            $nextNum = 1;
            if ($lastPresupuesto && preg_match('/^PR-(\d+)$/', $lastPresupuesto->number, $matches)) {
                $nextNum = intval($matches[1]) + 1;
            }
            $number = sprintf("PR-%d", $nextNum);

            $presupuesto = Presupuesto::create([
                'number' => $number,
                'client_id' => $datosValidados['client_id'],
                'date' => strtotime($datosValidados['date']),
                'due_date' => !empty($datosValidados['due_date']) ? date('Y-m-d', strtotime($datosValidados['due_date'])) : null,
                'status' => PresupuestoStatus::PENDING,
                'notes' => $datosValidados['notes'] ?? null,
                'description' => $datosValidados['description'] ?? null,
            ]);

            $this->sincronizarLineas($presupuesto, $datosValidados['lineas']);

            return $presupuesto;
        });
    }

    /**
     * Actualiza un presupuesto y sus líneas dentro de una transacción.
     */
    public function actualizarPresupuesto(Presupuesto $presupuesto, array $datosValidados): Presupuesto
    {
        return DB::transaction(function () use ($presupuesto, $datosValidados) {
            $presupuesto->update([
                'client_id' => $datosValidados['client_id'],
                'date' => strtotime($datosValidados['date']),
                'due_date' => !empty($datosValidados['due_date']) ? date('Y-m-d', strtotime($datosValidados['due_date'])) : null,
                'status' => isset($datosValidados['status']) ? PresupuestoStatus::tryFrom((int)$datosValidados['status']) : $presupuesto->status,
                'notes' => $datosValidados['notes'] ?? null,
                'description' => $datosValidados['description'] ?? null,
            ]);

            $this->sincronizarLineas($presupuesto, $datosValidados['lineas']);
            
            // Forzar actualización de fecha para caché si no hubo cambios en cabecera
            $presupuesto->touch();

            return $presupuesto;
        });
    }

    /**
     * Sincroniza (recrea) las líneas de un presupuesto y recalcula totales.
     * Usando variables en español para la lógica interna.
     */
    private function sincronizarLineas(Presupuesto $presupuesto, array $lineas): void
    {
        $presupuesto->lineas()->delete();

        $importeSubtotal = 0;
        $importeIva = 0;
        $importeIrpf = 0;

        foreach ($lineas as $linea) {
            $cantidad = (float) $linea['cantidad'];
            $precio = (float) $linea['precio_unitario'];
            $porcentajeIva = (float) ($linea['porcentaje_iva'] ?? 0);
            $porcentajeIrpf = (float) ($linea['porcentaje_irpf'] ?? 0);

            $totalLinea = $cantidad * $precio;
            
            $importeSubtotal += $totalLinea;
            $importeIva += $totalLinea * ($porcentajeIva / 100);
            $importeIrpf += $totalLinea * ($porcentajeIrpf / 100);

            $presupuesto->lineas()->create([
                'concepto' => $linea['concepto'],
                'descripcion' => $linea['descripcion'] ?? null,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'porcentaje_iva' => $porcentajeIva,
                'porcentaje_irpf' => $porcentajeIrpf,
                'total_linea' => $totalLinea,
            ]);
        }

        $presupuesto->updateQuietly([
            'subtotal' => $importeSubtotal,
            'tax_amount' => $importeIva,
            'irpf_amount' => $importeIrpf,
            'total' => $importeSubtotal + $importeIva - $importeIrpf,
        ]);
    }

    /**
     * Sube el documento PDF a Google Drive de forma asíncrona.
     */
    public function guardarEnDriveAsync(Presupuesto $presupuesto): void
    {
        try {
            $presupuesto->loadMissing('cliente');
            
            $pdfContent = $this->pdfService->generatePresupuestoPdf($presupuesto);
            
            $año = date('Y', is_numeric($presupuesto->date) ? $presupuesto->date : strtotime($presupuesto->date));
            $pathCarpetas = [(string) $año];

            $nombreCliente = $presupuesto->cliente ? $presupuesto->cliente->name : 'Cliente';
            $nombreSeguroCliente = str_replace(['/', '\\'], '-', $nombreCliente);
            $numeroSeguroDoc = str_replace(['/', '\\'], '-', $presupuesto->number ?? (string)$presupuesto->id);
            
            $sufijo = '';
            if ($presupuesto->status === PresupuestoStatus::CANCELED) {
                $sufijo = ' (Anulado)';
            } elseif ($presupuesto->status === PresupuestoStatus::REJECTED) {
                $sufijo = ' (Rechazado)';
            }
            
            $nombreArchivo = "{$numeroSeguroDoc} - {$nombreSeguroCliente}{$sufijo}.pdf";
            
            $rootId = env('GOOGLE_DRIVE_FOLDER_ID_PRESUPUESTOS');

            $fileId = $this->googleDriveService->uploadDocument(
                'google_presupuestos',
                $rootId,
                $pathCarpetas,
                $nombreArchivo,
                $pdfContent,
                $presupuesto->google_drive_file_id
            );

            if ($fileId && $fileId !== $presupuesto->google_drive_file_id) {
                $presupuesto->updateQuietly(['google_drive_file_id' => $fileId]);
            }
            
        } catch (Exception $e) {
            Log::error('Fallo al guardar presupuesto en Google Drive: ' . $e->getMessage());
        }
    }

    /**
     * Envía el presupuesto por correo electrónico.
     */
    public function enviarPresupuestoPorEmail(Presupuesto $presupuesto, array $datosEnvio, $usuarioLogueado = null): void
    {
        $pdfOutput = $this->pdfService->generatePresupuestoPdf($presupuesto);

        $mail = Mail::to($datosEnvio['email']);

        if (!empty($datosEnvio['cc_emails'])) {
            $ccList = array_map('trim', explode(',', $datosEnvio['cc_emails']));
            $ccList = array_filter($ccList, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
            if (!empty($ccList)) {
                $mail->cc($ccList);
            }
        }

        if (!empty($datosEnvio['send_copy_to_me']) && $usuarioLogueado) {
            $mail->bcc($usuarioLogueado->email);
        }

        $mail->send(new \App\Mail\PresupuestoPdfMail(
            $presupuesto, 
            $pdfOutput, 
            $datosEnvio['message'] ?? null, 
            $datosEnvio['attachments'] ?? []
        ));
    }
}
