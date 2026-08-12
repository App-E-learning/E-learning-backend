<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exercice;

/**
 * Contrôleur "coquille" : ces pages ne font que rendre le Blade, toutes
 * les données (chapitres, exercice existant, corrigé...) sont chargées
 * côté client via fetch() vers l'API JSON déjà en place (/api/*), pour
 * ne jamais dupliquer la logique métier des contrôleurs Api\*.
 */
class ExerciceViewController extends Controller
{
    public function index()
    {
        return view('admin.exercices.index');
    }

    public function create()
    {
        return view('admin.exercices.form', ['exerciceId' => null]);
    }

    public function edit(Exercice $exercice)
    {
        return view('admin.exercices.form', ['exerciceId' => $exercice->id]);
    }
}