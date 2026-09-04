<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * On stocke un CHEMIN relatif (ex: "photos/3_a1b2c3.jpg"), jamais une
     * URL absolue — l'URL complète est recalculée à la volée dans
     * User::getPhotoUrlAttribute() via Storage::disk('public')->url(...),
     * pour ne jamais figer l'adresse du serveur en base de données.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('telephone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};