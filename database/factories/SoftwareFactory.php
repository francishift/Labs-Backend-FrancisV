<?php

namespace Database\Factories;

use App\Models\Software;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Software>
 */
class SoftwareFactory extends Factory
{
    protected $model = Software::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => $this->faker->randomElement(['Software', 'Hosting']),
            'nombre' => $this->faker->word(),
            'descripcion' => $this->faker->sentence(),
            'tipo_licencia' => $this->faker->randomElement(['Anual', 'Mensual']),
            'precio' => $this->faker->randomFloat(2, 10, 500),
            'estado' => $this->faker->randomElement(['Activa', 'Finalizada']),
        ];
    }
}
