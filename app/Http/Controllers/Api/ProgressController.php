<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chapitre;
use App\Models\Exercice;
use Illuminate\Http\Request;

/**
 * Progression PERSONNELLE de l'élève connecté, calculée à partir de ses
 * soumissions réelles (table `soumissions`, alimentée par
 * ExerciceSoumissionController). Chaque élève ne voit que ses propres
 * données ($request->user() détermine le filtre — jamais un paramètre
 * fourni par le client).
 */
class ProgressController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $soumissions = $user->soumissions()
            ->orderByDesc('created_at')
            ->get();

        $exercicesFaits = $soumissions->pluck('exercice_id')->unique();
        $totalTentatives = $soumissions->count();
        $tentativesCorrectes = $soumissions->where('correct', true)->count();
        $scoreMoyen = $totalTentatives > 0 ? round($tentativesCorrectes / $totalTentatives, 2) : 0;

        // Dernière tentative par exercice = statut "actuel" de maîtrise
        // (les soumissions sont déjà triées par date décroissante, donc
        // le premier élément de chaque groupe est la plus récente).
        $derniereTentativeParExercice = $soumissions->groupBy('exercice_id')->map->first();
        $exercicesReussis = $derniereTentativeParExercice->where('correct', true)->count();

        // Série de jours consécutifs (jusqu'à aujourd'hui) avec au moins
        // une soumission.
        $joursAvecActivite = $soumissions->pluck('created_at')->map(fn ($d) => $d->toDateString())->unique();
        $streak = 0;
        $curseur = now()->startOfDay();
        while ($joursAvecActivite->contains($curseur->toDateString())) {
            $streak++;
            $curseur->subDay();
        }

        // Activité des 7 derniers jours (pour le graphique en barres).
        $joursSemaine = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
        $debutSemaine = now()->startOfWeek();
        $activiteSemaine = collect(range(0, 6))->map(function ($i) use ($debutSemaine, $soumissions, $joursSemaine) {
            $jour = $debutSemaine->copy()->addDays($i);
            $count = $soumissions->filter(fn ($s) => $s->created_at->isSameDay($jour))->count();

            return ['day' => $joursSemaine[$i], 'count' => $count];
        });

        // Maîtrise par chapitre : proportion des exercices disponibles du
        // chapitre dont la DERNIÈRE tentative de l'élève est correcte.
        // Note : une requête par chapitre — acceptable pour le volume MVP,
        // à optimiser (une seule requête groupée) si le nombre de chapitres
        // grandit significativement.
        $masteryParChapitre = Chapitre::orderBy('ordre')->get()->map(function (Chapitre $chapitre) use ($derniereTentativeParExercice) {
            $idsExercices = Exercice::disponibles()->where('chapitre_id', $chapitre->id)->pluck('id');
            $total = $idsExercices->count();
            $reussis = $idsExercices->filter(
                fn ($id) => optional($derniereTentativeParExercice->get($id))->correct === true
            )->count();

            return [
                'chapitre_id' => $chapitre->id,
                'titre' => $chapitre->titre,
                'mastery' => $total > 0 ? round($reussis / $total, 2) : 0,
            ];
        });

        return response()->json([
            'streak' => $streak,
            'exercices_faits' => $exercicesFaits->count(),
            'exercices_reussis' => $exercicesReussis,
            'score_moyen' => $scoreMoyen,
            'activite_semaine' => $activiteSemaine,
            'mastery_par_chapitre' => $masteryParChapitre,
            'badges' => [
                ['cle' => 'streak_7', 'label' => '7 jours de suite', 'icone' => 'flame', 'obtenu' => $streak >= 7],
                ['cle' => 'premier_chapitre', 'label' => 'Premier chapitre maîtrisé', 'icone' => 'ribbon', 'obtenu' => $masteryParChapitre->contains(fn ($c) => $c['mastery'] >= 1)],
                ['cle' => 'cent_exercices', 'label' => '100 exercices', 'icone' => 'trophy', 'obtenu' => $exercicesFaits->count() >= 100],
            ],
        ]);
    }
}