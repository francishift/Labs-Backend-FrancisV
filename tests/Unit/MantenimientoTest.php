<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Mantenimiento;
use App\Models\MantenimientoPrecio;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MantenimientoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Fijamos una fecha de prueba predeterminada global para estos tests
        Carbon::setTestNow(Carbon::create(2024, 6, 15));
    }

    public function test_calculate_month_income_returns_zero_before_start_date()
    {
        $mantenimiento = Mantenimiento::factory()->create([
            'fecha_inicio' => '2024-03-15',
            'importe' => 100,
            'tipo_pago' => 'mensual'
        ]);

        // Enero de 2024 es anterior a la fecha de inicio (Mazo 2024)
        $income = $mantenimiento->calculatePeriodIncome(1, 2024);

        $this->assertEquals(0, $income);
    }

    public function test_calculate_month_income_returns_zero_after_end_date()
    {
        $mantenimiento = Mantenimiento::factory()->create([
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-05-15',
            'importe' => 100,
            'tipo_pago' => 'mensual'
        ]);

        // Junio de 2024 es posterior a la fecha de fin (Mayo 2024)
        $income = $mantenimiento->calculatePeriodIncome(6, 2024);

        $this->assertEquals(0, $income);
    }

    public function test_calculate_month_income_applies_monthly_rate()
    {
        $mantenimiento = Mantenimiento::factory()->create([
            'fecha_inicio' => '2024-01-01',
            'importe' => 120,
            'tipo_pago' => 'mensual'
        ]);

        $income = $mantenimiento->calculatePeriodIncome(3, 2024);

        $this->assertEquals(120, $income);
    }

    public function test_calculate_month_income_applies_quarterly_rate()
    {
        $mantenimiento = Mantenimiento::factory()->create([
            'fecha_inicio' => '2024-01-01',
            'importe' => 300,
            'tipo_pago' => 'trimestral'
        ]);

        $income = $mantenimiento->calculatePeriodIncome(3, 2024);

        // 300 entre 3 = 100
        $this->assertEquals(100, $income);
    }

    public function test_calculate_month_income_applies_annual_rate()
    {
        $mantenimiento = Mantenimiento::factory()->create([
            'fecha_inicio' => '2024-01-01',
            'importe' => 1200,
            'tipo_pago' => 'anual'
        ]);

        $income = $mantenimiento->calculatePeriodIncome(5, 2024);

        // 1200 entre 12 = 100
        $this->assertEquals(100, $income);
    }

    public function test_calculate_month_income_uses_historical_price_snapshot()
    {
        // El factory por defecto creará un registro inicial en mantenimiento_precios 
        // a través de su observer 'booted'.
        $mantenimiento = Mantenimiento::factory()->create([
            'fecha_inicio' => '2023-01-01',
            'importe' => 100,
            'tipo_pago' => 'mensual'
        ]);

        // Simulamos un cambio de precio que aplicará a partir de Mayo 2024
        // Creamos un snapshot directo en precios
        $mantenimiento->precios()->create([
            'importe' => 150,
            'tipo_pago' => 'mensual',
            'fecha_aplicacion' => '2024-05-01'
        ]);

        // Abril debe cobrar 100
        $incomeApril = $mantenimiento->calculatePeriodIncome(4, 2024);
        $this->assertEquals(100, $incomeApril);

        // Mayo debe cobrar 150
        $incomeMay = $mantenimiento->calculatePeriodIncome(5, 2024);
        $this->assertEquals(150, $incomeMay);
    }

    public function test_calculate_period_income_all_sums_twelve_months()
    {
        $mantenimiento = Mantenimiento::factory()->create([
            'fecha_inicio' => '2024-01-01',
            'importe' => 50,
            'tipo_pago' => 'mensual'
        ]);

        $income = $mantenimiento->calculatePeriodIncome('all', 2024);

        // 12 meses completos = 50 * 12 = 600
        $this->assertEquals(600, $income);
    }
}
