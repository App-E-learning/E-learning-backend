<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\ChapitreController;
use App\Http\Controllers\Api\ExerciceController;
use App\Http\Controllers\Api\ExerciceImportController;
use App\Http\Controllers\Api\MatiereController;
use App\Http\Controllers\Api\NiveauController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SequenceController;
use Illuminate\Support\Facades\Route;

// Routes publiques (pas d'auth requise)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Routes protégées (token Sanctum requis) — voir section 8.1 du cahier des charges
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

    // Référentiel pédagogique (section 8.2)
    Route::apiResource('matieres', MatiereController::class);
    Route::apiResource('niveaux', NiveauController::class);
    Route::apiResource('sequences', SequenceController::class);
    Route::apiResource('chapitres', ChapitreController::class);

    // Banque d'exercices (section 8.3)
    // Lecture accessible aux deux rôles (élève consulte, admin gère) ;
    // le corrigé n'est chargé que pour les admins (garde-fou dans le contrôleur).
    Route::apiResource('exercices', ExerciceController::class)->only(['index', 'show']);

    // Écriture réservée aux admins
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('exercices', ExerciceController::class)->only(['store', 'update', 'destroy']);
    });

// Back-office réservé aux admins (section 8.8 + import OCR section 4.1)
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::post('exercices/import', [ExerciceImportController::class, 'store']);
        Route::get('exercices/brouillons', [ExerciceImportController::class, 'brouillons']);
        Route::post('exercices/{exercice}/valider', [ExerciceImportController::class, 'valider']);
    });

    // Routes réservées aux élèves (section 8.5, 8.6)
    Route::middleware('role:eleve')->group(function () {
        // Futures routes: prochain exercice, soumission de réponse...
    });
});