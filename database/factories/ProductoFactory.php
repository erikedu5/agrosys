<?php

namespace Database\Factories;

use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'id_clasificacion' => CatClasificacion::factory(),
            'id_marca' => CatMarca::factory(),
            'cantidad' => $this->faker->numberBetween(0, 100),
            'precio_unitario' => $this->faker->numberBetween(100, 1200),
            'precio_ieps' => $this->faker->numberBetween(100, 1200),
            'ieps' => $this->faker->numberBetween(3, 9),
            'tamano' => $this->faker->numberBetween(3, 1),
            'id_usuario' => User::factory(),
            'ingrediente_activo' => $this->faker->text(20),
            'id_empresa' => function (array $attributes) {
                $user = User::with('sucursal')->findOrFail($attributes['id_usuario']);

                return $user->id_empresa ?? $user->sucursal->id_empresa;
            },
        ];
    }
}
