<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Configuracion;
use App\Models\Factura;
use App\Models\Presupuesto;
use App\Models\Client;
use App\Models\Mantenimiento;
use App\Models\Proyecto;

class DocumentPdfService
{
    /**
     * Genera el contenido binario del PDF para una Factura
     */
    public function generateFacturaPdf(Factura $factura): string
    {
        $factura->loadMissing(['lineas', 'cliente']);
        
        $logoBase64 = $this->getLogoBase64();
        $config = $this->getConfigList();

        $pdf = Pdf::loadView('pdf.factura', [
            'factura' => $factura,
            'logoBase64' => $logoBase64,
            'configList' => $config
        ]);

        return $pdf->output();
    }

    /**
     * Genera el contenido binario del PDF para un Presupuesto
     */
    public function generatePresupuestoPdf(Presupuesto $presupuesto): string
    {
        $presupuesto->loadMissing(['lineas', 'cliente']);
        
        $logoBase64 = $this->getLogoBase64();
        $config = $this->getConfigList();

        $pdf = Pdf::loadView('pdf.presupuesto', [
            'presupuesto' => $presupuesto,
            'logoBase64' => $logoBase64,
            'configList' => $config
        ]);

        return $pdf->output();
    }

    /**
     * Genera la instancia PDF para un Cliente (incluye presupuestos)
     * Retorna el objeto PDF para que el controlador decida si stream() o download()
     */
    public function generateClientPdf(Client $client)
    {
        $client->loadMissing([
            'proyectos' => fn($q) => $q->orderBy('fecha_inicio', 'desc'),
            'proyectos.extensiones',
            'mantenimientos' => fn($q) => $q->orderBy('fecha_inicio', 'desc'),
            'mantenimientos.extensiones'
        ]);

        $presupuestos = Presupuesto::where('client_id', $client->id)
            ->orderBy('date', 'desc')
            ->get();

        $logoBase64 = $this->getLogoBase64();

        return Pdf::loadView('pdf.client', compact(
            'client', 
            'presupuestos', 
            'logoBase64'
        ));
    }

    /**
     * Genera la instancia PDF para un reporte de Mantenimiento
     * Retorna el objeto PDF para que el controlador decida si stream(), download() o output()
     */
    public function generateMantenimientoPdf(Mantenimiento $mantenimiento, $month, $year, bool $hidePrices = false)
    {
        $mantenimiento->loadMissing([
            'cliente:id,name,email,phone,mobile',
            'extensiones:id,nombre,precio,tipo_licencia',
            'servicios' => function($q) use ($year, $month) {
                $q->when($year, function ($query) use ($year) {
                    $query->whereYear('fecha', $year);
                })
                ->when($month !== 'all', function ($query) use ($month) {
                    $query->whereMonth('fecha', $month);
                })
                ->orderBy('fecha', 'desc')
                ->orderBy('created_at', 'desc');
            },
        ]);

        $stats = $mantenimiento->getFinancialStats($month, $year);
        $logoBase64 = $this->getLogoBase64();

        return Pdf::loadView('pdf.mantenimiento', [
            'mantenimiento' => $mantenimiento,
            'logoBase64' => $logoBase64,
            'stats' => $stats,
            'periodo' => [
                'month' => $month,
                'year' => $year
            ],
            'precioHoraFallback' => $mantenimiento->precio_hora ?: Mantenimiento::getDiscountedHourlyRate(),
            'hidePrices' => $hidePrices,
        ]);
    }

    /**
     * Genera la instancia PDF para un reporte de Proyecto
     */
    public function generateProyectoPdf(Proyecto $proyecto)
    {
        $proyecto->loadMissing([
            'client',
            'servicios' => fn($q) => $q->orderBy('fecha', 'desc')->orderBy('created_at', 'desc'),
            'extensiones',
        ]);

        $stats = $proyecto->getFinancialStats();
        $logoBase64 = $this->getLogoBase64();

        return Pdf::loadView('pdf.proyecto', array_merge(
            compact('proyecto', 'logoBase64'),
            $stats
        ));
    }

    private function getLogoBase64(): ?string
    {
        $logoPath = public_path('img/logo.png');
        
        if (!file_exists($logoPath)) {
            $logoPath = public_path('logo-icono.png'); // Fallback
        }
        
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
            return 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
        }

        return null;
    }

    private function getConfigList(): array
    {
        return [
            'empresa_nombre' => Configuracion::get('empresa_nombre', ''),
            'empresa_nif' => Configuracion::get('empresa_nif', ''),
            'empresa_direccion' => Configuracion::get('empresa_direccion', ''),
            'empresa_email' => Configuracion::get('empresa_email', ''),
            'empresa_telefono' => Configuracion::get('empresa_telefono', ''),
            'empresa_banco_nombre' => Configuracion::get('empresa_banco_nombre', ''),
            'empresa_banco_iban' => Configuracion::get('empresa_banco_iban', ''),
            'default_vencimiento_dias' => Configuracion::get('default_vencimiento_dias', 30),
        ];
    }
}
