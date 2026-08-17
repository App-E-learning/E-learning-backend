<?php

namespace Database\Seeders;

use App\Models\Chapitre;
use App\Models\Exercice;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Sequence;
use Illuminate\Database\Seeder;

/**
 * Données de démo minimales pour que l'app mobile ait un chapitre débloqué
 * avec des exercices dessus dès l'installation. Sans ce seeder, la base
 * ne contient qu'une matière (MatiereSeeder) — aucun niveau, séquence,
 * chapitre ni exercice, donc GET /chapitres?debloques=1 renvoie toujours
 * un tableau vide côté mobile.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $matiere = Matiere::firstOrCreate(
            ['nom' => 'Mathématiques'],
            ['code' => 'MATH']
        );

        $niveau = Niveau::firstOrCreate(
            ['code' => 'TC'],
            ['nom' => 'Terminale C']
        );

        // date_debut dans le passé => estDebloquee() = true dès le seed.
        $sequence = Sequence::firstOrCreate(
            ['niveau_id' => $niveau->id, 'ordre' => 1],
            ['nom' => '1ère séquence', 'date_debut' => now()->subDay(), 'date_fin' => null]
        );

        $suites = Chapitre::firstOrCreate(
            ['matiere_id' => $matiere->id, 'niveau_id' => $niveau->id, 'sequence_id' => $sequence->id, 'titre' => 'Suites numériques'],
            ['description' => 'Suites arithmétiques, géométriques, limites.', 'ordre' => 1]
        );

        $derivation = Chapitre::firstOrCreate(
            ['matiere_id' => $matiere->id, 'niveau_id' => $niveau->id, 'sequence_id' => $sequence->id, 'titre' => 'Dérivation'],
            ['description' => "Nombre dérivé, fonction dérivée, étude de variations.", 'ordre' => 2]
        );

        $this->qcm(
            $suites,
            "Soit (uₙ) définie par u₀ = 2 et uₙ₊₁ = 3uₙ − 4. Quelle est la nature de cette suite ?",
            ['Arithmétique de raison 3', 'Géométrique de raison 3', 'Ni arithmétique ni géométrique', 'Constante'],
            ['Constante'],
            "Le point fixe vérifie x = 3x − 4, soit x = 2. Comme u₀ = 2 est déjà ce point fixe, la suite reste constante : uₙ = 2 pour tout n."
        );

        $this->qcm(
            $suites,
            "(uₙ) est arithmétique de raison 3, avec u₀ = 5. Que vaut u₁₀ ?",
            ['35', '30', '32', '38'],
            ['35'],
            "Pour une suite arithmétique, uₙ = u₀ + n×r. Donc u₁₀ = 5 + 10×3 = 35."
        );

        $this->texteCourt(
            $suites,
            "On considère la suite (uₙ) définie par u₀ = 1 et, pour tout entier naturel n, uₙ₊₁ = (2uₙ + 3) / 5. Calculer u₁.",
            ['1'],
            "u₁ = (2×1 + 3)/5 = 1."
        );

        $this->qcm(
            $derivation,
            "Soit f(x) = x³ − 2x + 1. Que vaut f'(1) ?",
            ['1', '2', '3', '0'],
            ['1'],
            "f'(x) = 3x² − 2, donc f'(1) = 3×1 − 2 = 1."
        );

        $this->qcm(
            $derivation,
            "Pour f(x) = x², quel est le coefficient directeur de la tangente en x = 2 ?",
            ['2', '4', '8', '1'],
            ['4'],
            "f'(x) = 2x, donc f'(2) = 4 : c'est le coefficient directeur de la tangente en x = 2."
        );
    }

    private function qcm(Chapitre $chapitre, string $enonce, array $options, array $reponsesCorrectes, string $explication): void
    {
        $exercice = Exercice::firstOrCreate(
            ['chapitre_id' => $chapitre->id, 'enonce' => $enonce],
            [
                'type' => 'qcm',
                'options' => $options,
                'difficulte' => 2,
                'annee_origine' => now()->year,
                'origine' => 'saisie_manuelle',
                'statut' => 'valide',
            ]
        );

        $exercice->corrige()->firstOrCreate([], [
            'reponses_correctes' => $reponsesCorrectes,
            'explication_officielle' => $explication,
        ]);
    }

    private function texteCourt(Chapitre $chapitre, string $enonce, array $reponsesCorrectes, string $explication): void
    {
        $exercice = Exercice::firstOrCreate(
            ['chapitre_id' => $chapitre->id, 'enonce' => $enonce],
            [
                'type' => 'texte_court',
                'options' => null,
                'difficulte' => 2,
                'annee_origine' => now()->year,
                'origine' => 'saisie_manuelle',
                'statut' => 'valide',
            ]
        );

        $exercice->corrige()->firstOrCreate([], [
            'reponses_correctes' => $reponsesCorrectes,
            'explication_officielle' => $explication,
        ]);
    }
}