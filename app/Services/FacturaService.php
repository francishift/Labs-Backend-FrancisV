<?php

namespace App\Services;

use App\Models\Factura;
use App\Models\Presupuesto;
use App\Models\Configuracion;
use App\Enums\FacturaStatus;
use App\Enums\PresupuestoStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

class FacturaService
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
     * Crea una nueva factura junto con sus líneas dentro de una transacción.
     */
    public function crearFactura(array $datosValidados): Factura
    {
        return DB::transaction(function () use ($datosValidados) {
            $lastFactura = Factura::where('number', 'like', 'FV-%')
                ->orderByRaw('CAST(SUBSTRING(number, 4) AS UNSIGNED) DESC')
                ->lockForUpdate()
                ->first();

            $nextNum = 1;
            if ($lastFactura && preg_match('/^FV-(\d+)$/', $lastFactura->number, $matches)) {
                $nextNum = intval($matches[1]) + 1;
            }
            $number = sprintf("FV-%d", $nextNum);

            $factura = Factura::create([
                'number' => $number,
                'client_id' => $datosValidados['client_id'],
                'proyecto_id' => $datosValidados['proyecto_id'] ?? null,
                'date' => strtotime($datosValidados['date']),
                'due_date' => !empty($datosValidados['due_date']) ? strtotime($datosValidados['due_date']) : null,
                'status' => FacturaStatus::PENDING,
                'notes' => $datosValidados['notes'] ?? null,
                'description' => $datosValidados['description'] ?? null,
            ]);

            $this->sincronizarLineas($factura, $datosValidados['lineas']);

            return $factura;
        });
    }

    /**
     * Actualiza una factura y sus líneas dentro de una transacción.
     */
    public function actualizarFactura(Factura $factura, array $datosValidados): Factura
    {
        return DB::transaction(function () use ($factura, $datosValidados) {
            $factura->update([
                'client_id' => $datosValidados['client_id'],
                'proyecto_id' => $datosValidados['proyecto_id'] ?? null,
                'date' => strtotime($datosValidados['date']),
                'due_date' => !empty($datosValidados['due_date']) ? strtotime($datosValidados['due_date']) : null,
                'status' => isset($datosValidados['status']) ? FacturaStatus::tryFrom((int)$datosValidados['status']) : $factura->status,
                'notes' => $datosValidados['notes'] ?? null,
                'description' => $datosValidados['description'] ?? null,
            ]);

            $this->sincronizarLineas($factura, $datosValidados['lineas']);
            
            // Forzar actualización de fecha para caché si no hubo cambios en cabecera
            $factura->touch();

            return $factura;
        });
    }

    /**
     * Convierte un presupuesto existente en una nueva factura.
     * Copia cabecera y líneas. Marca el presupuesto como INVOICED.
     * Usa lockForUpdate para garantizar numeración correlativa.
     */
    public function convertirDesdePresupuesto(Presupuesto $presupuesto): Factura
    {
        return DB::transaction(function () use ($presupuesto) {
            $presupuesto->loadMissing('lineas');

            // Obtener siguiente número de factura con bloqueo para garantizar correlatividad
            $lastFactura = Factura::where('number', 'like', 'FV-%')
                ->orderByRaw('CAST(SUBSTRING(number, 4) AS UNSIGNED) DESC')
                ->lockForUpdate()
                ->first();

            $nextNum = 1;
            if ($lastFactura && preg_match('/^FV-(\d+)$/', $lastFactura->number, $matches)) {
                $nextNum = intval($matches[1]) + 1;
            }
            $number = sprintf('FV-%d', $nextNum);

            $defaultVencimientoDias = Configuracion::get('default_vencimiento_dias', 30);

            $factura = Factura::create([
                'number'       => $number,
                'client_id'    => $presupuesto->client_id,
                'proyecto_id'  => null,
                'date'         => time(),
                'due_date'     => strtotime('+' . $defaultVencimientoDias . ' days'),
                'status'       => FacturaStatus::PENDING,
                'notes'        => $presupuesto->notes,
                'description'  => $presupuesto->description,
                'subtotal'     => $presupuesto->subtotal,
                'tax_amount'   => $presupuesto->tax_amount,
                'irpf_amount'  => $presupuesto->irpf_amount,
                'total'        => $presupuesto->total,
            ]);

            // Copiar líneas del presupuesto a la factura
            foreach ($presupuesto->lineas as $linea) {
                $factura->lineas()->create([
                    'concepto'         => $linea->concepto,
                    'descripcion'      => $linea->descripcion,
                    'cantidad'         => $linea->cantidad,
                    'precio_unitario'  => $linea->precio_unitario,
                    'porcentaje_iva'   => $linea->porcentaje_iva,
                    'porcentaje_irpf'  => $linea->porcentaje_irpf,
                    'total_linea'      => $linea->total_linea,
                ]);
            }

            // Marcar el presupuesto como facturado
            $presupuesto->updateQuietly(['status' => PresupuestoStatus::INVOICED]);

            return $factura;
        });
    }

    /**
     * Duplica una factura existente.
     */
    public function duplicarFactura(Factura $facturaOriginal): Factura
    {
        return DB::transaction(function () use ($facturaOriginal) {
            $facturaOriginal->loadMissing('lineas');

            $lastFactura = Factura::where('number', 'like', 'FV-%')
                ->orderByRaw('CAST(SUBSTRING(number, 4) AS UNSIGNED) DESC')
                ->lockForUpdate()
                ->first();

            $nextNum = 1;
            if ($lastFactura && preg_match('/^FV-(\d+)$/', $lastFactura->number, $matches)) {
                $nextNum = intval($matches[1]) + 1;
            }
            $number = sprintf("FV-%d", $nextNum);

            $defaultVencimientoDias = Configuracion::get('default_vencimiento_dias', 30);
            $defaultDueDate = strtotime('+' . $defaultVencimientoDias . ' days');

            $nuevaFactura = Factura::create([
                'number' => $number,
                'client_id' => $facturaOriginal->client_id,
                'proyecto_id' => $facturaOriginal->proyecto_id,
                'date' => time(),
                'due_date' => $defaultDueDate,
                'status' => FacturaStatus::PENDING,
                'notes' => $facturaOriginal->notes,
                'description' => $facturaOriginal->description,
                'subtotal' => $facturaOriginal->subtotal,
                'tax_amount' => $facturaOriginal->tax_amount,
                'irpf_amount' => $facturaOriginal->irpf_amount,
                'total' => $facturaOriginal->total,
            ]);

            foreach ($facturaOriginal->lineas as $linea) {
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

            return $nuevaFactura;
        });
    }

    /**
     * Sincroniza (recrea) las líneas de una factura y recalcula totales.
     * Esta función usa variables en español para lógica intermedia.
     */
    private function sincronizarLineas(Factura $factura, array $lineas): void
    {
        $factura->lineas()->delete();

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

            $factura->lineas()->create([
                'concepto' => $linea['concepto'],
                'descripcion' => $linea['descripcion'] ?? null,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'porcentaje_iva' => $porcentajeIva,
                'porcentaje_irpf' => $porcentajeIrpf,
                'total_linea' => $totalLinea,
            ]);
        }

        $factura->updateQuietly([
            'subtotal' => $importeSubtotal,
            'tax_amount' => $importeIva,
            'irpf_amount' => $importeIrpf,
            'total' => $importeSubtotal + $importeIva - $importeIrpf,
        ]);
    }

    /**
     * Sube el documento PDF de la factura a Google Drive de forma asíncrona (deferred).
     */
    public function guardarEnDriveAsync(Factura $factura): void
    {
        try {
            // Recargar relaciones necesarias para nombres y pdf
            $factura->loadMissing('cliente');
            
            $pdfContent = $this->pdfService->generateFacturaPdf($factura);
            
            $año = date('Y', is_numeric($factura->date) ? $factura->date : strtotime($factura->date));
            $mes = is_numeric($factura->date) ? date('n', $factura->date) : date('n', strtotime($factura->date));
            $trimestre = ceil($mes / 3);
            
            $pathCarpetas = [
                $año,
                'VENTAS',
                "{$trimestre}tri"
            ];

            $nombreCliente = $factura->cliente ? $factura->cliente->name : 'Cliente';
            $nombreSeguroCliente = str_replace(['/', '\\'], '-', $nombreCliente);
            $numeroSeguroDoc = str_replace(['/', '\\'], '-', $factura->number ?? (string)$factura->id);
            
            $sufijo = '';
            if ($factura->status === FacturaStatus::CANCELED) {
                $sufijo = ' (Anulada)';
            }
            
            $nombreArchivo = "{$numeroSeguroDoc} - {$nombreSeguroCliente}{$sufijo}.pdf";
            
            $rootId = env('GOOGLE_DRIVE_FOLDER_ID_FACTURAS');

            $fileId = $this->googleDriveService->uploadDocument(
                'google_facturas',
                $rootId,
                $pathCarpetas,
                $nombreArchivo,
                $pdfContent,
                $factura->google_drive_file_id
            );

            if ($fileId && $fileId !== $factura->google_drive_file_id) {
                $factura->updateQuietly(['google_drive_file_id' => $fileId]);
            }
            
        } catch (Exception $e) {
            Log::error('Fallo al guardar factura en Google Drive: ' . $e->getMessage());
        }
    }

    /**
     * Envía la factura por correo electrónico.
     */
    public function enviarFacturaPorEmail(Factura $factura, array $datosEnvio, $usuarioLogueado = null): void
    {
        $pdfOutput = $this->pdfService->generateFacturaPdf($factura);

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

        $mail->send(new \App\Mail\FacturaPdfMail(
            $factura, 
            $pdfOutput, 
            $datosEnvio['message'] ?? null, 
            $datosEnvio['attachments'] ?? []
        ));
    }
}
