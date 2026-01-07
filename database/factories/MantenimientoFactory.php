<?php

namespace Database\Factories;

use App\Models\Mantenimiento;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class MantenimientoFactory extends Factory
{
    protected $model = Mantenimiento::class;

    public function definition()
    {
        return [
            'aplicacion' => $this->faker->word . ' App',
            'client_id' => Client::factory(),
            'fecha_inicio' => now(),
            'tipo_pago' => 'mensual',
            'importe' => 100,
            'estado' => 'en curso',
        ];
    }
}
