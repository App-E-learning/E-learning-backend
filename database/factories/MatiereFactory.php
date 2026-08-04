<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MatiereFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => 'Mathématiques',
            'code' => strtoupper($this->faker->unique()->lexify('MAT???')),
            'description' => $this->faker->sentence(),
        ];
    }
}