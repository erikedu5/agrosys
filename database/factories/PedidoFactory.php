<?php

namespace Database\Factories;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Sucursales;
use Illuminate\Database\Eloquent\Factories\Factory;

class PedidoFactory extends Factory
{
    protected $model = Pedido::class;

    public function definition(): array
    {
        return [
            'id_sucursal' => Sucursales::factory(),
            'id_producto' => Producto::factory(),
            'cantidad' => $this->faker->numberBetween(1, 10),
            'nombre_solicitante' => $this->faker->name(),
            'numero_solicitante' => $this->faker->phoneNumber(),
            'completado' => false,
        ];
    }
}
