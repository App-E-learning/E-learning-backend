<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['eleve', 'admin'])->default('eleve')->after('email');
            $table->string('niveau')->nullable()->after('role'); // ex: "Terminale C"
            $table->foreignId('matiere_id')->nullable()->after('niveau')
                ->constrained('matieres')->nullOnDelete();
            $table->string('telephone')->nullable()->unique()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['matiere_id']);
            $table->dropColumn(['role', 'niveau', 'matiere_id', 'telephone']);
        });
    }
};