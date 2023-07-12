<?php

namespace Database\Factories;

use App\Models\CatMarca;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\catMarca>
 */
class CatMarcaFactory extends Factory
{
    
    protected $model = CatMarca::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->text(120),
        ];
    }
}
