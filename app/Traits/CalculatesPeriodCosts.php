<?php

namespace App\Traits;

trait CalculatesPeriodCosts
{
    /**
     * Calcula el coste para un periodo dado.
     * 
     * @param string|int $month 'all' para anual o 1-12 para un mes específico.
     * @param float|null $overridePrecio Precio opcional para usar (útil para snapshots).
     * @return float
     */
    public function calculatePeriodCost($month = 'all', ?float $overridePrecio = null): float
    {
        $precio = $overridePrecio ?? (float) $this->precio;
        $tipo = strtolower($this->tipo_licencia);

        if ($month === 'all') {
            // Vista anual
            if ($tipo === 'anual') return $precio;
            if ($tipo === 'mensual') return $precio * 12;
            return $precio; // Pago único o desconocido
        } else {
            // Vista mensual
            if ($tipo === 'anual') return $precio / 12;
            if ($tipo === 'mensual') return $precio;
            return $precio / 12; // Pago único - lo prorrateamos por 12
        }
    }
}
