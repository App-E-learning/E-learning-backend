<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\ChapitreController;
use App\Http\Controllers\Api\ExerciceController;
use App\Http\Controllers\Api\ExerciceImportController;
use App\Http\Controllers\Api\ExerciceSoumissionController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\MatiereController;
use App\Http\Controllers\Api\NiveauController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SequenceController;
use App\Http\Controllers\Api\ExplicationController;
use Illuminate\Support\Facades\Route;

// Routes publiques (pas d'auth requise)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Lecture publique du référentiel pédagogique (matières, niveaux) :
// nécessaire pour peupler le formulaire d'inscription AVANT que l'élève
// ait un compte/token. Rien de sensible n'est exposé ici (juste nom/code).
Route::apiResource('matieres', MatiereController::class)->only(['index', 'show']);
Route::apiResource('niveaux', NiveauController::class)->only(['index', 'show']);

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

    // Référentiel pédagogique (section 8.2) — la lecture (index/show) de
    // matieres/niveaux est publique ci-dessus ; seule l'écriture reste ici,
    // réservée aux admins (gestion back-office).
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('matieres', MatiereController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('niveaux', NiveauController::class)->only(['store', 'update', 'destroy']);
    });
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
        Route::post('exercices/import-auto', [ExerciceImportController::class, 'storeAuto']);
        Route::post('exercices/brouillon', [ExerciceImportController::class, 'storeTexte']);
        Route::get('exercices/brouillons', [ExerciceImportController::class, 'brouillons']);
        Route::post('exercices/{exercice}/valider', [ExerciceImportController::class, 'valider']);
        Route::post('ia/proposer-correction', [\App\Http\Controllers\Api\Admin\AiCorrectionController::class, 'proposer']);
        Route::post('ia/decouper-exercices', [\App\Http\Controllers\Api\Admin\AiCorrectionController::class, 'decouper']);
        Route::post('chapitres/{chapitre}/generer-qcm', [\App\Http\Controllers\Api\Admin\QcmGenerationController::class, 'genererPourChapitre']);
    });

    // Routes réservées aux élèves (section 8.5, 8.6)
    Route::middleware('role:eleve')->group(function () {
        // Corrige côté serveur, sans jamais exposer reponses_correctes
        // avant que l'élève ait effectivement soumis sa réponse.
        Route::post('exercices/{exercice}/soumettre', [ExerciceSoumissionController::class, 'store']);

        // Progression personnelle, calculée à partir des soumissions de
        // l'élève connecté uniquement (voir ProgressController).
        Route::get('progress', [ProgressController::class, 'index']);
    });
    Route::post('explications', [ExplicationController::class, 'generer']);
});