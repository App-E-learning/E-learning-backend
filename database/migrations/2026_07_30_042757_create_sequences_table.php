<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('niveau_id')->constrained()->cascadeOnDelete();
            $table->string('nom');           // ex: "1ère séquence"
            $table->unsignedTinyInteger('ordre'); // 1, 2, 3... pour trier et calculer le déblocage
            $table->date('date_debut');      // date de déblocage
            $table->date('date_fin')->nullable();
            $table->timestamps();

            $table->unique(['niveau_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};