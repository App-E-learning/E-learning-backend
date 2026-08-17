<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenererQcmRequest;
use App\Models\Chapitre;
use App\Models\Exercice;
use App\Services\AiQcmGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Throwable;

class QcmGenerationController extends Controller
{
    public function __construct(private readonly AiQcmGenerationService $service)
    {
    }

    /**
     * Génère un lot de QCM inédits sur ce chapitre (difficulté croissante)
     * et les enregistre directement en base, corrigé compris. Par défaut
     * (auto_valider=true), ils sont immédiatement visibles aux élèves —
     * c'est le point demandé : plus besoin de ressaisir des exercices à la
     * main pour ce chapitre, l'IA en produit un lot prêt à l'emploi.
     */
    public function genererPourChapitre(GenererQcmRequest $request, Chapitre $chapitre): JsonResponse
    {
        $nombre = $request->validated('nombre', 5);
        $difficulteDepart = $request->validated('difficulte_depart', 1);
        $autoValider = $request->boolean('auto_valider', true);

        try {
            $questions = $this->service->genererPourChapitre(
                $chapitre->titre,
                $chapitre->description,
                $nombre,
                $difficulteDepart,
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $exercices = DB::transaction(function () use ($questions, $chapitre, $autoValider) {
            return collect($questions)->map(function ($q) use ($chapitre, $autoValider) {
                $exercice = Exercice::create([
                    'chapitre_id' => $chapitre->id,
                    'enonce' => $q['enonce'],
                    'type' => 'qcm',
                    'options' => $q['options'],
                    'difficulte' => $q['difficulte'],
                    'annee_origine' => now()->year,
                    'origine' => 'generation_ia',
                    'statut' => $autoValider ? 'valide' : 'brouillon',
                ]);

                $exercice->corrige()->create([
                    'reponses_correctes' => $q['reponses_correctes'],
                    'explication_officielle' => $q['explication_officielle'],
                ]);

                return $exercice->load('corrige');
            });
        });

        return response()->json([
            'message' => "{$exercices->count()} QCM généré(s) pour le chapitre \"{$chapitre->titre}\".",
            'data' => $exercices->map(fn (Exercice $e) => [
                'id' => $e->id,
                'enonce' => $e->enonce,
                'options' => $e->options,
                'difficulte' => $e->difficulte,
                'statut' => $e->statut,
                'reponses_correctes' => $e->corrige->reponses_correctes,
                'explication_officielle' => $e->corrige->explication_officielle,
            ]),
        ], Response::HTTP_CREATED);
    }
}