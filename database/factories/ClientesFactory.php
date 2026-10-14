<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Clientes;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\clientes>
 */
class ClientesFactory extends Factory
{



    protected $model = Clientes::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->text(20),
            'porcentaje_descuento' => $this->faker->numberBetween(1, 30),
            'adeudo_total' => 0,
            'abono_total' => 0,
            'balance' => 0,
            'requiereFactura' => 0,
            'rfc' => '',
            'activo' => 0,
            'id_sucursal' => 1,
        ];
    }
}
