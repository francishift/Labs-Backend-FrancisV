<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Mantenimiento;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class MantenimientoTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_period_income_mensual()
    {
        $mantenimiento = Mantenimiento::factory()->create([
            'tipo_pago' => 'mensual',
            'importe' => 100
        ]);

        $this->assertEquals(100, $mantenimiento->calculatePeriodIncome(1));
        $this->assertEquals(1200, $mantenimiento->calculatePeriodIncome('all'));
    }

    public function test_calculate_period_income_trimestral()
    {
        $mantenimiento = Mantenimiento::factory()->create([
            'fecha_inicio' => Carbon::create(2024, 1, 1),
            'tipo_pago' => 'trimestral',
            'importe' => 300
        ]);

        // Enero (Mes 1) -> Toca pago
        $this->assertEquals(300, $mantenimiento->calculatePeriodIncome(1));
        // Febrero (Mes 2) -> No toca pago
        $this->assertEquals(0, $mantenimiento->calculatePeriodIncome(2));
        // Abril (Mes 4) -> Toca pago
        $this->assertEquals(300, $mantenimiento->calculatePeriodIncome(4));
        
        $this->assertEquals(1200, $mantenimiento->calculatePeriodIncome('all'));
    }

    public function test_calculate_period_income_anual()
    {
        $mantenimiento = Mantenimiento::factory()->create([
            'fecha_inicio' => Carbon::create(2024, 6, 1),
            'tipo_pago' => 'anual',
            'importe' => 1200
        ]);

        $this->assertEquals(1200, $mantenimiento->calculatePeriodIncome(6));
        $this->assertEquals(0, $mantenimiento->calculatePeriodIncome(1));
        $this->assertEquals(1200, $mantenimiento->calculatePeriodIncome('all'));
    }
}
