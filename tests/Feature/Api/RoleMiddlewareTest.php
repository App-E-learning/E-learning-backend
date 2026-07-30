<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function defineRoutes($router): void
    {
        Route::middleware(['auth:sanctum', 'role:admin'])
            ->get('/api/test-admin-only', fn () => response()->json(['ok' => true]));
    }

    public function test_un_eleve_ne_peut_pas_acceder_a_une_route_admin(): void
    {
        $user = User::factory()->create(['role' => 'eleve']);
        $token = $user->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/test-admin-only')
            ->assertStatus(403);
    }

    public function test_un_admin_peut_acceder_a_une_route_admin(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/test-admin-only')
            ->assertStatus(200);
    }
}