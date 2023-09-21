<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SolucionEnfermedad>
 */
class SolucionEnfermedadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_producto' => $this->faker->numberBetween(1, 10),
            'id_enfermedad_tipo_flor' => $this->faker->numberBetween(1, 10),
            'dosis_bomba_ml' => $this->faker->numberBetween(1, 40),
            'dosis_tambo_ml' => $this->faker->numberBetween(1, 40),
            'id_sucursal' => 1,
        ];
    }
}
