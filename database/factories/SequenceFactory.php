<?php

namespace Database\Factories;

use App\Models\Niveau;
use Illuminate\Database\Eloquent\Factories\Factory;

class SequenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'niveau_id' => Niveau::factory(),
            'nom' => $this->faker->randomElement(['1ère séquence', '2ème séquence', '3ème séquence']),
            'ordre' => $this->faker->unique()->numberBetween(1, 6),
            'date_debut' => now()->subDays(10), // débloquée par défaut
            'date_fin' => null,
        ];
    }

    // "State" : une variante de la factory. Permet d'écrire dans un test :
    // Sequence::factory()->future()->create()
    public function future(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_debut' => now()->addDays(30), // pas encore débloquée
        ]);
    }

    public function passee(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_debut' => now()->subDays(30),
        ]);
    }
}