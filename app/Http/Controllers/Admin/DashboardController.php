<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chapitre;
use App\Models\Exercice;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Sequence;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'matieres' => Matiere::count(),
            'niveaux' => Niveau::count(),
            'sequences' => Sequence::count(),
            'chapitres' => Chapitre::count(),
            'exercices' => Exercice::count(),
            'brouillons' => Exercice::brouillons()->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}