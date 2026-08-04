<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapitre_id')->constrained()->cascadeOnDelete();
            $table->text('enonce');
            $table->string('image_url')->nullable();
            $table->enum('type', ['qcm', 'numerique', 'texte_court']);
            $table->json('options')->nullable(); // liste des choix si type = qcm
            $table->unsignedTinyInteger('difficulte'); // 1 à 5
            $table->year('annee_origine');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercices');
    }
};