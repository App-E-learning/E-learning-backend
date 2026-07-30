<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NiveauFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => 'Terminale C',
            'code' => strtoupper($this->faker->unique()->lexify('NIV???')),
        ];
    }
}