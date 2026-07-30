<?php

namespace Tests\Feature\Api;

use App\Models\Matiere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_utilisateur_peut_voir_son_profil(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/profile')
            ->assertStatus(200)
            ->assertJsonPath('id', $user->id);
    }

    public function test_un_utilisateur_peut_mettre_a_jour_son_nom(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/profile', ['name' => 'Nouveau Nom'])
            ->assertStatus(200)
            ->assertJsonPath('name', 'Nouveau Nom');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nouveau Nom']);
    }

    public function test_changement_mot_de_passe_refuse_si_ancien_incorrect(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/profile/password', [
                'current_password' => 'mauvais',
                'password' => 'nouveauMdp123',
                'password_confirmation' => 'nouveauMdp123',
            ])
            ->assertStatus(422);
    }

    public function test_changement_mot_de_passe_reussi(): void
    {
        $user = User::factory()->create(); // mot de passe par défaut: password123
        $token = $user->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/profile/password', [
                'current_password' => 'password123',
                'password' => 'nouveauMdp123',
                'password_confirmation' => 'nouveauMdp123',
            ])
            ->assertStatus(200);
    }
}