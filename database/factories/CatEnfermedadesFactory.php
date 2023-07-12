<?php

namespace Database\Factories;

use App\Models\CatEnfermedades;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\catEnfermedades>
 */
class CatEnfermedadesFactory extends Factory
{
    
    protected $model = CatEnfermedades::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->text(120),
            'descripcion' => $this->faker->text(210),
        ];
    }
}
