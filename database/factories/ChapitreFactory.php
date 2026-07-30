<?php

namespace Database\Factories;

use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Sequence;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChapitreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'matiere_id' => Matiere::factory(),
            'niveau_id' => Niveau::factory(),
            'sequence_id' => Sequence::factory(),
            'titre' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'ordre' => $this->faker->numberBetween(1, 10),
        ];
    }
}