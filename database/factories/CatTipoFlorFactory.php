<?php

namespace Database\Factories;

use App\Models\CatTipoFlor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\catTipoFlor>
 */
class CatTipoFlorFactory extends Factory
{
    
    protected $model = CatTipoFlor::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->text(120),
        ];
    }
}
