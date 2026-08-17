<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExerciceViewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Back-office admin (section 8.8) — pages Blade servies en même temps que
// l'API (même origine), qui pilotent l'API JSON existante via fetch().
// Voir /mnt/backend/README pour le détail de l'authentification (session
// Sanctum "stateful", cf. bootstrap/app.php).
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware('admin.web')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::view('matieres', 'admin.matieres')->name('matieres');
        Route::view('niveaux', 'admin.niveaux')->name('niveaux');
        Route::view('sequences', 'admin.sequences')->name('sequences');
        Route::view('chapitres', 'admin.chapitres')->name('chapitres');

        Route::get('exercices', [ExerciceViewController::class, 'index'])->name('exercices');
        Route::get('exercices/creer', [ExerciceViewController::class, 'create'])->name('exercices.create');
        Route::get('exercices/{exercice}/modifier', [ExerciceViewController::class, 'edit'])->name('exercices.edit');

        Route::view('import', 'admin.import')->name('import');
        Route::view('brouillons', 'admin.brouillons')->name('brouillons');
        Route::view('generer-qcm', 'admin.generer-qcm')->name('generer-qcm');
    });
});