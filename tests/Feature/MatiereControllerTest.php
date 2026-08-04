<?php

namespace Tests\Feature;

use App\Models\Matiere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatiereControllerTest extends TestCase
{
    // RefreshDatabase : migre une base de test fraîche avant CHAQUE test,
    // et l'enveloppe dans une transaction annulée à la fin. Résultat :
    // chaque test démarre sur une base vide, sans pollution entre eux.
    use RefreshDatabase;

    public function test_un_utilisateur_non_authentifie_ne_peut_pas_lister_les_matieres(): void
    {
        $response = $this->getJson('/api/matieres');

        $response->assertStatus(401);
    }

    public function test_un_utilisateur_authentifie_peut_lister_les_matieres(): void
    {
        $user = User::factory()->create();
        Matiere::factory()->count(3)->create();

        // actingAs simule une session authentifiée pour ce user,
        // via le guard "sanctum" déclaré dans vos routes
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/matieres');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data'); // JsonResource::collection enveloppe dans "data"
    }

    public function test_on_peut_creer_une_matiere(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/matieres', [
            'nom' => 'Mathématiques',
            'code' => 'MATH',
            'description' => 'Matière pilote du MVP',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.nom', 'Mathématiques')
            ->assertJsonPath('data.code', 'MATH');

        // assertDatabaseHas : vérifie directement en base, indépendamment
        // de la réponse JSON — utile pour être sûr que ça a vraiment persisté
        $this->assertDatabaseHas('matieres', ['code' => 'MATH']);
    }

    public function test_la_creation_echoue_si_le_code_est_deja_pris(): void
    {
        $user = User::factory()->create();
        Matiere::factory()->create(['code' => 'MATH']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/matieres', [
            'nom' => 'Autre matière',
            'code' => 'MATH', // doublon volontaire
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_on_peut_modifier_une_matiere(): void
    {
        $user = User::factory()->create();
        $matiere = Matiere::factory()->create(['nom' => 'Ancien nom']);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/matieres/{$matiere->id}", ['nom' => 'Nouveau nom']);

        $response->assertStatus(200)->assertJsonPath('data.nom', 'Nouveau nom');
    }

    public function test_on_peut_supprimer_une_matiere(): void
    {
        $user = User::factory()->create();
        $matiere = Matiere::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/matieres/{$matiere->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('matieres', ['id' => $matiere->id]);
    }
}