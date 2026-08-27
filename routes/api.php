<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\ChapitreController;
use App\Http\Controllers\Api\ExerciceController;
use App\Http\Controllers\Api\ExerciceImportController;
use App\Http\Controllers\Api\ExerciceSoumissionController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\MatiereController;
use App\Http\Controllers\Api\NiveauController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PushTokenController;
use App\Http\Controllers\Api\SequenceController;
use App\Http\Controllers\Api\ExplicationController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::apiResource('matieres', MatiereController::class)->only(['index', 'show']);
Route::apiResource('niveaux', NiveauController::class)->only(['index', 'show']);

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

    // Notifications push (rappel quotidien) — un appareil s'enregistre à la
    // connexion, se désenregistre à la déconnexion.
    Route::post('device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('device-tokens', [DeviceTokenController::class, 'destroy']);

    Route::post('push-token', [PushTokenController::class, 'store']);
    Route::delete('push-token', [PushTokenController::class, 'destroy']);

    Route::middleware('role:admin')->group(function () {
        Route::apiResource('matieres', MatiereController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('niveaux', NiveauController::class)->only(['store', 'update', 'destroy']);
    });
    Route::apiResource('sequences', SequenceController::class);
    Route::apiResource('chapitres', ChapitreController::class);

    Route::apiResource('exercices', ExerciceController::class)->only(['index', 'show']);

    Route::middleware('role:admin')->group(function () {
        Route::apiResource('exercices', ExerciceController::class)->only(['store', 'update', 'destroy']);
    });

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

    Route::middleware('role:eleve')->group(function () {
        Route::post('exercices/{exercice}/soumettre', [ExerciceSoumissionController::class, 'store']);
        Route::get('progress', [ProgressController::class, 'index']);
    });
    Route::post('explications', [ExplicationController::class, 'generer']);
});
