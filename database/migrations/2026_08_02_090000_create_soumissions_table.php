<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historique des réponses soumises par les élèves (via
     * POST /exercices/{id}/soumettre). C'est la seule source de vérité
     * pour tout calcul de progression (streak, score moyen, maîtrise par
     * chapitre) — absente du schéma jusqu'ici, ce qui empêchait l'écran
     * "Progression" d'afficher quoi que ce soit de réel par utilisateur.
     */
    public function up(): void
    {
        Schema::create('soumissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercice_id')->constrained()->cascadeOnDelete();
            $table->json('reponse'); // chaîne ou tableau selon le type d'exercice
            $table->boolean('correct');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'exercice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soumissions');
    }
};