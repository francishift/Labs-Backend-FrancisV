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
        if ($quarter) {
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $startMonth + 2;
            $startPeriod = strtotime("$year-$startMonth-01 00:00:00");
            $endPeriod = strtotime(date("Y-m-t 23:59:59", strtotime("$year-$endMonth-01")));
            $query->whereBetween('date', [$startPeriod, $endPeriod]);
        } else {
            $startPeriod = strtotime("$year-01-01 00:00:00");
            $endPeriod = strtotime("$year-12-31 23:59:59");
            $query->whereBetween('date', [$startPeriod, $endPeriod]);
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
            $startMonth = ($quarter - 1) * 3 + 1;
            $query->whereMonth('date', '>=', $startMonth)
                  ->whereMonth('date', '<=', $startMonth + 2);
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
