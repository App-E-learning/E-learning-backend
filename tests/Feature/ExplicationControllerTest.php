<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExplicationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_genere_une_explication_via_lapi_gemini(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'id' => 'v1_test123',
                'status' => 'completed',
                'steps' => [
                    [
                        'type' => 'model_output',
                        'content' => [
                            ['type' => 'text', 'text' => "L'erreur vient d'un mauvais signe."],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/explications', [
            'enonce' => 'Résoudre x^2 - 4 = 0',
            'corrige_officiel' => 'x = 2 ou x = -2',
            'reponse_eleve' => 'x = 2',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.explication', "L'erreur vient d'un mauvais signe.");

        $this->assertDatabaseCount('explications', 1);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'v1beta/interactions')
                && str_contains($request['input'], 'x^2 - 4 = 0');
        });
    }

    public function test_une_deuxieme_demande_identique_utilise_le_cache(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'status' => 'completed',
                'steps' => [
                    [
                        'type' => 'model_output',
                        'content' => [
                            ['type' => 'text', 'text' => 'Explication générée.'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $payload = [
            'enonce' => 'Calculer la dérivée de f(x) = x^2',
            'corrige_officiel' => "f'(x) = 2x",
        ];

        $this->actingAs($user, 'sanctum')->postJson('/api/explications', $payload)->assertStatus(201);
        $second = $this->actingAs($user, 'sanctum')->postJson('/api/explications', $payload);

        $second->assertStatus(200);
        Http::assertSentCount(1);
        $this->assertDatabaseCount('explications', 1);
    }

    public function test_retourne_une_erreur_502_si_lapi_gemini_echoue(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'server error'], 500),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/explications', [
            'enonce' => 'Un énoncé',
            'corrige_officiel' => 'Un corrigé',
        ]);

        $response->assertStatus(502);
        $this->assertDatabaseCount('explications', 0);
    }

    public function test_la_validation_refuse_une_requete_sans_corrige(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/explications', [
            'enonce' => 'Un énoncé',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('corrige_officiel');
    }
}