<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un utilisateur peut avoir plusieurs appareils (téléphone + tablette,
     * ou réinstallation de l'app) — d'où une table séparée plutôt qu'une
     * colonne unique sur `users`. `token` est UNIQUE : si le même token
     * Expo réapparaît sous un autre compte (appareil partagé, changement
     * de compte sur le même téléphone), on réattribue la ligne au nouvel
     * utilisateur plutôt que de dupliquer (voir DeviceTokenController).
     */
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->enum('platform', ['ios', 'android', 'web'])->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};