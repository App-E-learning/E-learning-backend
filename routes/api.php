<?php

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProfileController;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('password', [ProfileController::class, 'updatePassword']);
    });
});
// Routes publiques (pas d'auth requise)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Routes protégées (token Sanctum requis)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Les autres modules (exercices, scoring, recommandation...)
    // viendront se brancher ici, protégés par le même middleware.
});
// routes/api.php

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Ex: futures routes du back-office (8.8) — CRUD chapitres, import exercices...
    // Route::apiResource('chapitres', ChapitreController::class);
});

Route::middleware(['auth:sanctum', 'role:eleve'])->group(function () {
    // Ex: futures routes élève — prochain exercice, soumission de réponse...
});

// Routes accessibles aux deux rôles (déjà déclarées à l'étape 6)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});