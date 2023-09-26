<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\sucursal>
 */
class SucursalesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->name(),
            'direccion' => $this->faker->text(20),
            'telefono' => $this->faker->numberBetween(1, 10),
            'email' => $this->faker->unique()->safeEmail(),
            'es_matriz' => true,
            'id_empresa' => 1,
        ];
    }
}
