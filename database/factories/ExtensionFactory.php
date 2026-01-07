<?php

namespace Database\Factories;

use App\Models\Extension;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExtensionFactory extends Factory
{
    protected $model = Extension::class;

    public function definition()
    {
        return [
            'nombre' => $this->faker->word . ' Extension',
            'precio' => 10,
            'tipo_licencia' => 'Anual',
        ];
    }
}
