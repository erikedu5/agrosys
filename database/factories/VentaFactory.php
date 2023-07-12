<?php

namespace Database\Factories;

use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Venta>
 */
class VentaFactory extends Factory
{
    
    protected $model = Venta::class;

    public function definition(): array
    {
        return [
            'total' => $this->faker->randomFloat(2),
            'id_usuario' => $this->faker->numberBetween(1, 10),
            'id_cliente' => $this->faker->numberBetween(1, 10),
            'tipo_venta' => $this->faker->text(10),
            'venta_pagada' => $this->faker->boolean(),
            'fecha_pago' => $this->faker->date(),
        ];
    }
}
