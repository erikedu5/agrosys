<?php

namespace Database\Factories;

use App\Models\EnfermedadesTipoFlor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EnfermedadesTipoFlor>
 */
class EnfermedadesTipoFlorFactory extends Factory
{
    
    protected $model = EnfermedadesTipoFlor::class;

    public function definition(): array
    {
        return [
            'id_tipo_flor' => $this->faker->numberBetween(1, 10),
            'id_enfermedad' => $this->faker->numberBetween(1, 10),
        ];
    }
}
