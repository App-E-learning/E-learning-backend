<?php

namespace Tests\Feature;

use App\Models\Chapitre;
use App\Models\Sequence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChapitreDeblocageTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_chapitre_dont_la_sequence_est_passee_est_debloque(): void
    {
        $sequence = Sequence::factory()->passee()->create();
        $chapitre = Chapitre::factory()->create(['sequence_id' => $sequence->id]);

        // Test UNITAIRE de la méthode du modèle, sans passer par HTTP :
        // rapide, et isole précisément ce qu'on vérifie
        $this->assertTrue($chapitre->estDebloque());
    }

    public function test_un_chapitre_dont_la_sequence_est_future_est_verrouille(): void
    {
        $sequence = Sequence::factory()->future()->create();
        $chapitre = Chapitre::factory()->create(['sequence_id' => $sequence->id]);

        $this->assertFalse($chapitre->estDebloque());
    }

    public function test_lapi_expose_correctement_le_statut_est_debloque(): void
    {
        $user = User::factory()->create();
        $sequence = Sequence::factory()->passee()->create();
        $chapitre = Chapitre::factory()->create(['sequence_id' => $sequence->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/chapitres/{$chapitre->id}");

        $response->assertStatus(200)->assertJsonPath('data.est_debloque', true);
    }

    public function test_le_filtre_debloques_ne_retourne_que_les_chapitres_accessibles(): void
    {
        $user = User::factory()->create();

        $sequencePassee = Sequence::factory()->passee()->create();
        $sequenceFuture = Sequence::factory()->future()->create();

        $chapitreDebloque = Chapitre::factory()->create(['sequence_id' => $sequencePassee->id]);
        Chapitre::factory()->create(['sequence_id' => $sequenceFuture->id]); // verrouillé

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/chapitres?debloques=1');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $chapitreDebloque->id);
    }

    public function test_une_sequence_qui_debute_exactement_aujourdhui_est_debloquee(): void
    {
        // Cas limite (edge case) important : le jour même du début, le
        // chapitre doit déjà être accessible, pas seulement le lendemain
        $sequence = Sequence::factory()->create(['date_debut' => now()->startOfDay()]);

        $this->assertTrue($sequence->estDebloquee());
    }

    public function test_on_peut_simuler_une_date_future_pour_verifier_le_deblocage_automatique(): void
    {
        // travelTo() : déplace le temps "vu" par l'application vers une
        // date précise, sans toucher à l'horloge système. Permet de tester
        // "si on est le 15 septembre, ce chapitre devient débloqué"
        $sequence = Sequence::factory()->create(['date_debut' => '2026-09-15']);
        $chapitre = Chapitre::factory()->create(['sequence_id' => $sequence->id]);

        $this->travelTo('2026-09-10'); // avant la date de début
        $this->assertFalse($chapitre->fresh('sequence')->estDebloque());

        $this->travelTo('2026-09-15'); // pile le jour J
        $this->assertTrue($chapitre->fresh('sequence')->estDebloque());

        $this->travelBack(); // remet l'horloge normale pour ne pas fausser les autres tests
    }
}