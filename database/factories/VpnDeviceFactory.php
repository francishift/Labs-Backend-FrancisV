<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VpnDevice>
 */
class VpnDeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'public_key' => $this->faker->sha256(),
            'internal_ip' => '10.0.0.' . $this->faker->numberBetween(2, 254),
            'is_active' => true,
        ];
    }
}
