<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SoumettreReponseRequest;
use App\Models\Exercice;
use App\Models\Soumission;
use Illuminate\Http\Response;

/**
 * Corrige la réponse d'un élève côté serveur, sans jamais transmettre le
 * corrigé au client avant qu'il ait soumis une réponse (voir garde-fou
 * dans ExerciceController::index/show — with_corrige réservé aux admins).
 * Chaque soumission est persistée (table `soumissions`) : c'est la source
 * de données utilisée par ProgressController pour calculer la progression
 * réelle de l'élève (streak, score moyen, maîtrise par chapitre).
 */
class ExerciceSoumissionController extends Controller
{
    public function store(SoumettreReponseRequest $request, Exercice $exercice)
    {
        // Un élève ne doit pouvoir soumettre que sur un exercice réellement
        // disponible (chapitre débloqué + exercice validé), même s'il devine
        // un id valide au hasard.
        $disponible = Exercice::disponibles()->whereKey($exercice->id)->exists();

        if (! $disponible) {
            return response()->json([
                'message' => "Cet exercice n'est pas disponible.",
            ], Response::HTTP_FORBIDDEN);
        }

        $exercice->loadMissing('corrige');

        if (! $exercice->corrige) {
            return response()->json([
                'message' => "Cet exercice n'a pas encore de corrigé associé.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $reponseElève = $request->validated('reponse');
        $attendues = $exercice->corrige->reponses_correctes ?? [];

        // Pas de réponse fournie : l'élève demande juste le corrigé après
        // avoir travaillé sur papier (typiquement un exercice rédigé à
        // plusieurs sous-questions, sans réponse unique comparable). On ne
        // force alors aucun verdict vrai/faux — `correct` reste `null`,
        // distinct d'une réponse réellement fausse.
        $correct = ($reponseElève === null || $reponseElève === '')
            ? null
            : $this->estCorrecte($exercice->type, $reponseElève, $attendues);

        Soumission::create([
            'user_id' => $request->user()->id,
            'exercice_id' => $exercice->id,
            'reponse' => $reponseElève,
            'correct' => $correct,
        ]);

        return response()->json([
            'exercice_id' => $exercice->id,
            'correct' => $correct,
            // Le corrigé n'est révélé qu'à partir d'ici, une fois la
            // réponse de l'élève déjà enregistrée côté client — jamais avant.
            'reponses_correctes' => $attendues,
            'explication_officielle' => $exercice->corrige->explication_officielle,
        ]);
    }

    /**
     * Normalise puis compare la réponse envoyée aux réponses attendues.
     * - qcm : égalité exacte avec une des options correctes (ou, pour un
     *   qcm à choix multiples, même ensemble que reponses_correctes).
     * - numerique / texte_court : comparaison insensible à la casse et
     *   aux espaces superflus, contre n'importe laquelle des réponses
     *   acceptées (permet plusieurs formulations/valeurs valides).
     */
    private function estCorrecte(string $type, mixed $reponse, array $attendues): bool
    {
        if ($type === 'qcm' && is_array($reponse)) {
            $normalise = fn (array $v) => collect($v)->map(fn ($x) => mb_strtolower(trim((string) $x)))->sort()->values()->all();

            return $normalise($reponse) === $normalise($attendues);
        }

        $reponseNormalisee = mb_strtolower(trim((string) $reponse));

        foreach ($attendues as $attendue) {
            if ($reponseNormalisee === mb_strtolower(trim((string) $attendue))) {
                return true;
            }
        }

        return false;
    }
}