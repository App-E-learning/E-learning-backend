<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
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