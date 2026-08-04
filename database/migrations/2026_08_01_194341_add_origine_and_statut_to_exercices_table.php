<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercices', function (Blueprint $table) {
            $table->enum('origine', ['saisie_manuelle', 'import_ocr'])
                ->default('saisie_manuelle')->after('annee_origine');
            $table->enum('statut', ['brouillon', 'valide'])
                ->default('valide')->after('origine');
            $table->text('texte_ocr_brut')->nullable()->after('statut'); // trace du texte extrait, avant correction
        });
    }

    public function down(): void
    {
        Schema::table('exercices', function (Blueprint $table) {
            $table->dropColumn(['origine', 'statut', 'texte_ocr_brut']);
        });
    }
};