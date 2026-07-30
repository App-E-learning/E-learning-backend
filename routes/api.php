<?php

use App\Http\Controllers\Api\ChapitreController;
use App\Http\Controllers\Api\MatiereController;
use App\Http\Controllers\Api\NiveauController;
use App\Http\Controllers\Api\SequenceController;
use Illuminate\Support\Facades\Route;

// middleware('auth:sanctum') protège toutes ces routes : il faut un
// token Sanctum valide (l'app mobile l'obtient après le login) pour
// y accéder. Voir le point 8.1 du cahier des charges déjà en place.
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('matieres', MatiereController::class);
    Route::apiResource('niveaux', NiveauController::class);
    Route::apiResource('sequences', SequenceController::class);
    Route::apiResource('chapitres', ChapitreController::class);
});