<?php

namespace App\Services;

use App\Models\Factura;
use App\Models\PurchaseFactura;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    /**
     * Obtiene el resumen financiero de Ventas (Facturas) para un año y trimestre opcional.
     * Retorna el total (con IVA), subtotal (base_imponible) e IVA.
     *
     * @param int $year
     * @param int|null $quarter
     * @return array
     */
    public function getSalesSummary(int $year, ?int $quarter = null): array
    {
        $query = Factura::query();

        // Las fechas en Holded se guardan como timestamp en la columna 'date'
        $query->whereYear(DB::raw('FROM_UNIXTIME(date)'), $year);

        if ($quarter) {
            $query->whereRaw('QUARTER(FROM_UNIXTIME(date)) = ?', [$quarter]);
        }

        // Ahora usamos sum() directamente a la BBDD evitando cargar colecciones pesadas, 
        // exactamente igual que con PurchaseFacturas.
        $totalConIva = $query->sum('total');
        $subtotal = $query->sum('subtotal');
        $iva = $query->sum('tax_amount');

        return [
            'total' => round((float)$totalConIva, 2),
            'base_imponible' => round((float)$subtotal, 2),
            'iva' => round((float)$iva, 2),
        ];
    }

    /**
     * Obtiene el resumen financiero de Compras (PurchaseFacturas) para un año y trimestre opcional.
     * Retorna el total (con IVA), net_amount (base_imponible) y tax_amount (IVA).
     *
     * @param int $year
     * @param int|null $quarter
     * @return array
     */
    public function getPurchasesSummary(int $year, ?int $quarter = null): array
    {
        // En compras sí que tenemos columnas nativas (total, net_amount, tax_amount) y formato DATE.
        $query = PurchaseFactura::query();

        $query->whereYear('date', $year);

        if ($quarter) {
            $query->whereRaw('QUARTER(date) = ?', [$quarter]);
        }

        // Aquí sí podemos usar sum() directamente a la BBDD evitando cargar colecciones pesadas
        $totalConIva = $query->sum('total');
        $subtotal = $query->sum('net_amount');
        $iva = $query->sum('tax_amount');

        return [
            'total' => round((float)$totalConIva, 2),
            'base_imponible' => round((float)$subtotal, 2),
            'iva' => round((float)$iva, 2),
        ];
    }

    /**
     * Método auxiliar para envolver las peticiones clave (Año en curso y Trimestre actual).
     */
    public function getCurrentDashboardMetrics(): array
    {
        $now = Carbon::now();
        $currentYear = $now->year;
        $currentQuarter = $now->quarter;

        return [
            'ventas' => [
                'anual' => $this->getSalesSummary($currentYear),
                'trimestral' => $this->getSalesSummary($currentYear, $currentQuarter),
            ],
            'compras' => [
                'anual' => $this->getPurchasesSummary($currentYear),
                'trimestral' => $this->getPurchasesSummary($currentYear, $currentQuarter),
            ]
        ];
    }
}
