<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * `correct` était NOT NULL depuis la création de la table — mais
     * ExerciceSoumissionController enregistre volontairement `null` quand
     * l'élève consulte le corrigé d'un exercice rédigé sans avoir soumis de
     * réponse (auto-évaluation, voir estCorrecte()). Sans ce correctif,
     * chaque consultation de ce type plantait avec :
     * "SQLSTATE[23000]: Column 'correct' cannot be null".
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE soumissions MODIFY COLUMN correct BOOLEAN NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE soumissions SET correct = false WHERE correct IS NULL');
        DB::statement('ALTER TABLE soumissions MODIFY COLUMN correct BOOLEAN NOT NULL');
    }
};