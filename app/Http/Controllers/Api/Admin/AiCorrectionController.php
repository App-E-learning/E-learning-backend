<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DecouperExercicesRequest;
use App\Http\Requests\ProposerCorrectionRequest;
use App\Services\AiCorrectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

class AiCorrectionController extends Controller
{
    public function __construct(private readonly AiCorrectionService $service)
    {
    }

    /**
     * Découpe le texte d'une page scannée (plusieurs exercices) en blocs
     * distincts, sans rien résoudre. Ne crée rien en base — l'admin
     * choisit ensuite lesquels créer comme fiches séparées.
     */
    public function decouper(DecouperExercicesRequest $request): JsonResponse
    {
        try {
            $resultat = $this->service->decouperExercices($request->validated('texte'));
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['data' => $resultat]);
    }

    /**
     * Propose une correction (et, pour un QCM sans options suffisantes, des
     * options plausibles) à partir du seul énoncé. Ne modifie rien en base :
     * l'admin relit la proposition dans le formulaire puis l'enregistre
     * lui-même (ou la corrige avant).
     */
    public function proposer(ProposerCorrectionRequest $request): JsonResponse
    {
        try {
            $proposition = $this->service->proposerCorrection(
                $request->validated('enonce'),
                $request->validated('type'),
                $request->validated('options', []),
            );
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['data' => $proposition]);
    }
}