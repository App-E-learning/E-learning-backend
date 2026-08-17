<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * `origine` était un enum limité à 'saisie_manuelle' / 'import_ocr'
     * (voir 2026_08_01_194341_...). On ajoute 'generation_ia' pour tracer
     * les exercices entièrement générés par l'IA (nouveaux QCM créés à
     * partir d'un chapitre, sans énoncé source) — distinct d'un import OCR
     * qui digitalise un énoncé déjà existant.
     *
     * Laravel/Doctrine ne modifie pas proprement les colonnes enum, donc
     * ALTER TABLE en SQL brut (MySQL uniquement, comme le reste du projet).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE exercices MODIFY COLUMN origine ENUM('saisie_manuelle', 'import_ocr', 'generation_ia') NOT NULL DEFAULT 'saisie_manuelle'");
    }

    public function down(): void
    {
        DB::statement("UPDATE exercices SET origine = 'saisie_manuelle' WHERE origine = 'generation_ia'");
        DB::statement("ALTER TABLE exercices MODIFY COLUMN origine ENUM('saisie_manuelle', 'import_ocr') NOT NULL DEFAULT 'saisie_manuelle'");
    }
};