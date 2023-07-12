<?php

namespace Database\Factories;

use App\Models\CatClasificacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\catClasificacion>
 */
class CatClasificacionFactory extends Factory
{

    protected $model = CatClasificacion::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->text(20),
            'criterio' => $this->faker->text(20)
        ];
    }
}
