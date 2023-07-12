<?php

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Producto>
 */
class ProductoFactory extends Factory
{
    
    protected $model = Producto::class;
    
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->text(120),
            'id_clasificacion' => $this->faker->numberBetween(1, 10),
            'id_marca' => $this->faker->numberBetween(1, 10),
            'cantidad' => $this->faker->numberBetween(0, 100),
            'precio_unitario' => $this->faker->numberBetween(100, 1200), 
            'precio_ieps' => $this->faker->numberBetween(100, 1200), 
            'ieps' => $this->faker->numberBetween(3, 9), 
            'tamano' => $this->faker->numberBetween(3, 1),
            'id_usuario' => $this->faker->numberBetween(1, 10),
        ];
    }
}
