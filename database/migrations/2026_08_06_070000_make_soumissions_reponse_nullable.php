<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * `reponse` était NOT NULL depuis la création de la table — correct au
     * départ, mais devenu incompatible depuis qu'on autorise un élève à
     * consulter le corrigé d'un exercice rédigé SANS soumettre de réponse
     * (auto-évaluation sur papier, voir ExerciceSoumissionController et la
     * migration make_soumissions_correct_nullable). Sans ce correctif,
     * chaque consultation de corrigé plantait avec :
     * "SQLSTATE[23000]: Column 'reponse' cannot be null".
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE soumissions MODIFY COLUMN reponse JSON NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE soumissions SET reponse = JSON_QUOTE('') WHERE reponse IS NULL");
        DB::statement('ALTER TABLE soumissions MODIFY COLUMN reponse JSON NOT NULL');
    }
};