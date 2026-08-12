<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Crée un compte admin par défaut pour accéder au back-office
     * (aucun utilisateur admin n'était encore seedé dans le projet).
     * Identifiants de démo — à changer immédiatement en production.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@elearning.test'],
            [
                'name' => 'Administrateur',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );
    }
}