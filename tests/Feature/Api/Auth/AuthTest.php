<?php

namespace Tests\Feature\Api\Auth;

use App\Models\Matiere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_eleve_peut_s_inscrire(): void
    {
        $matiere = Matiere::factory()->create();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test Eleve',
            'email' => 'eleve@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'niveau' => 'Terminale C',
            'matiere_id' => $matiere->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role'], 'token']);

        $this->assertDatabaseHas('users', [
            'email' => 'eleve@test.com',
            'role' => 'eleve',
        ]);
    }

    public function test_inscription_refuse_email_et_telephone_absents(): void
    {
        $matiere = Matiere::factory()->create();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'niveau' => 'Terminale C',
            'matiere_id' => $matiere->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_inscription_refuse_niveau_hors_perimetre_mvp(): void
    {
        $matiere = Matiere::factory()->create();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test',
            'email' => 'x@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'niveau' => 'Première D',
            'matiere_id' => $matiere->id,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['niveau']);
    }

    public function test_un_eleve_peut_se_connecter(): void
    {
        $user = User::factory()->create(['email' => 'eleve@test.com']);

        $response = $this->postJson('/api/auth/login', [
            'identifiant' => 'eleve@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)->assertJsonStructure(['user', 'token']);
    }

    public function test_connexion_refusee_avec_mauvais_mot_de_passe(): void
    {
        User::factory()->create(['email' => 'eleve@test.com']);

        $response = $this->postJson('/api/auth/login', [
            'identifiant' => 'eleve@test.com',
            'password' => 'mauvais',
        ]);

        $response->assertStatus(422);
    }

    public function test_me_necessite_authentification(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_me_retourne_l_utilisateur_connecte(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('id', $user->id);
    }

    public function test_logout_revoque_le_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_refresh_invalide_l_ancien_token(): void
    {
        $user = User::factory()->create();
        $oldToken = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->postJson('/api/auth/refresh')
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }
}