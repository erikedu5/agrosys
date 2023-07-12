<?php

namespace Database\Factories;

use App\Models\ProductoVenta;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductoVenta>
 */
class ProductoVentaFactory extends Factory
{
    
    protected $model = ProductoVenta::class;

    public function definition(): array
    {
        return [
            'id_producto' => $this->faker->numberBetween(1, 10),
            'id_venta' => $this->faker->numberBetween(1, 10),
            'cantidad' => $this->faker->numberBetween(1, 100),
            'total_productos' => $this->faker->numberBetween(100, 10000),
        ];
    }
}
