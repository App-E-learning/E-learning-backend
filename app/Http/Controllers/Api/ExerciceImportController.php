<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportExerciceRequest;
use App\Http\Resources\ExerciceResource;
use App\Models\Exercice;
use App\Services\OcrService;
use Illuminate\Http\Response;

class ExerciceImportController extends Controller
{
    public function __construct(private OcrService $ocrService) {}

    /**
     * Importe un exercice digitalisé (image ou PDF scanné) via OCR.
     * L'exercice est créé en statut "brouillon" : il n'est jamais visible
     * côté élève tant qu'un admin ne l'a pas relu et validé (voir
     * Exercice::scopeDisponibles). Le corrigé n'est PAS créé automatiquement
     * — il doit être complété manuellement après relecture, l'OCR ne pouvant
     * pas fiablement distinguer énoncé et réponse correcte.
     */
    public function store(ImportExerciceRequest $request)
    {
        $resultat = $this->ocrService->extraireTexte(
            $request->file('fichier'),
            $request->boolean('forcer_vision_ia')
        );

        $exercice = Exercice::create([
            'chapitre_id' => $request->validated('chapitre_id'),
            'enonce' => $resultat['texte'],
            'type' => $request->validated('type'),
            'difficulte' => $request->validated('difficulte'),
            'annee_origine' => $request->validated('annee_origine'),
            'origine' => 'import_ocr',
            'statut' => 'brouillon',
            'texte_ocr_brut' => $resultat['texte'],
        ]);

        return (new ExerciceResource($exercice->load('chapitre')))
            ->additional(['ocr_methode' => $resultat['methode']])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Liste les exercices en attente de validation admin (brouillons OCR).
     */
    public function brouillons()
    {
        $exercices = Exercice::with('chapitre')->brouillons()->orderByDesc('id')->paginate(20);

        return ExerciceResource::collection($exercices);
    }

    /**
     * Valide un brouillon : le rend visible côté élève. À appeler une fois
     * que l'admin a relu/corrigé l'énoncé (via PUT /exercices/{id}) et
     * complété le corrigé.
     */
    public function valider(Exercice $exercice)
    {
        if (! $exercice->corrige) {
            return response()->json([
                'message' => 'Impossible de valider : cet exercice n\'a pas encore de corrigé associé.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $exercice->update(['statut' => 'valide']);

        return new ExerciceResource($exercice->load('chapitre', 'corrige'));
    }
}