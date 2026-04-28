<?php

namespace App\Services;

use App\Models\Proyecto;
use App\Models\Configuracion;
use App\Models\Software;
use App\Models\Factura;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\ProyectoPdfMail;

class ProyectoService
{
    private DocumentPdfService $pdfService;

    public function __construct(DocumentPdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function crearProyecto(array $datosValidados, ?array $extensiones = null, ?array $facturas = null): Proyecto
    {
        return DB::transaction(function () use ($datosValidados, $extensiones, $facturas) {
            $datosValidados['precio_hora'] = Configuracion::get('precio_hora', 0);
            $datosValidados['porcentaje_software'] = (float) Configuracion::get('porcentaje_software', 2);
            $datosValidados['coste_software_anual'] = Software::getTotalAnual();
            
            $proyecto = Proyecto::create($datosValidados);

            if ($extensiones !== null) {
                $proyecto->syncExtensionSnapshots($extensiones);
            }

            if ($facturas !== null) {
                Factura::whereIn('id', $facturas)->update(['proyecto_id' => $proyecto->id]);
            }

            return $proyecto;
        });
    }

    public function actualizarProyecto(Proyecto $proyecto, array $datosValidados, ?array $extensiones = null, ?array $facturas = null): void
    {
        DB::transaction(function () use ($proyecto, $datosValidados, $extensiones, $facturas) {
            $proyecto->update($datosValidados);

            if ($extensiones !== null) {
                $proyecto->syncExtensionSnapshots($extensiones);
            }

            // Desvincular todas las facturas actuales
            $proyecto->facturas()->update(['proyecto_id' => null]);
            
            // Vincular las nuevas facturas seleccionadas
            if ($facturas !== null) {
                Factura::whereIn('id', $facturas)->update(['proyecto_id' => $proyecto->id]);
            }
        });
    }

    public function enviarPdfPorEmail(Proyecto $proyecto, string $email): void
    {
        $pdf = $this->pdfService->generateProyectoPdf($proyecto);
        $pdfOutput = $pdf->output();

        Mail::to($email)->send(new ProyectoPdfMail($proyecto, $pdfOutput));
    }
}
