<?php

namespace Database\Factories;

use App\Models\Proyecto;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProyectoFactory extends Factory
{
    protected $model = Proyecto::class;

    public function definition()
    {
        return [
            'proyecto' => $this->faker->sentence(3),
            'client_id' => Client::factory(),
            'fecha_inicio' => now(),
            'presupuesto' => 1000,
            'estado' => 'En proceso',
        ];
    }
}
