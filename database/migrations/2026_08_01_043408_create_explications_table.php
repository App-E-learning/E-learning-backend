<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('explications', function (Blueprint $table) {
            $table->id();

            // Pas de ->constrained() : la table `exercices` n'existe pas
            // encore (tâche 8.3, à venir). On garde juste la référence en
            // entier simple, indexée pour les requêtes futures. À convertir
            // en vraie clé étrangère une fois le modèle Exercice créé.
            $table->unsignedBigInteger('exercice_id')->nullable()->index();

            // Clé de cache : identifie de façon unique une combinaison
            // exacte (énoncé + corrigé + réponse élève). Voir explication
            // détaillée plus bas dans le service.
            $table->string('cle_cache', 64)->unique();

            $table->text('enonce');
            $table->text('corrige_officiel');
            $table->text('reponse_eleve')->nullable();
            $table->text('contenu'); // l'explication générée par l'IA
            $table->string('modele_ia'); // pour audit : quel modèle a répondu
            $table->unsignedInteger('tokens_utilises')->nullable(); // suivi du coût

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('explications');
    }
};