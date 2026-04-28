<?php

namespace App\Services;

use App\Models\Mantenimiento;
use App\Models\Configuracion;
use App\Models\Software;
use Illuminate\Support\Facades\Mail;
use App\Mail\MantenimientoPdfMail;

class MantenimientoService
{
    private DocumentPdfService $pdfService;

    public function __construct(DocumentPdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function crearMantenimiento(array $datosValidados, ?array $extensiones = null): Mantenimiento
    {
        $datosValidados['precio_hora'] = Mantenimiento::getDiscountedHourlyRate();
        $datosValidados['porcentaje_software'] = (float) Configuracion::get('porcentaje_software', 2);
        $datosValidados['coste_software_anual'] = Software::getTotalAnual();
        
        $mantenimiento = Mantenimiento::create($datosValidados);
        
        if ($extensiones !== null) {
            $mantenimiento->syncExtensionSnapshots($extensiones);
        }

        return $mantenimiento;
    }

    public function actualizarMantenimiento(Mantenimiento $mantenimiento, array $datosValidados, ?array $extensiones = null): void
    {
        $mantenimiento->update($datosValidados);
        
        if ($extensiones !== null) {
            $mantenimiento->syncExtensionSnapshots($extensiones);
        }
    }

    public function enviarPdfPorEmail(Mantenimiento $mantenimiento, string $email, $month, $year, bool $hidePrices): void
    {
        $pdf = $this->pdfService->generateMantenimientoPdf($mantenimiento, $month, $year, $hidePrices);
        $pdfOutput = $pdf->output();

        Mail::to($email)->send(new MantenimientoPdfMail($mantenimiento, $pdfOutput, $month, $year));
    }
}
