<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExerciceRequest;
use App\Http\Requests\UpdateExerciceRequest;
use App\Http\Resources\ExerciceResource;
use App\Models\Exercice;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ExerciceController extends Controller
{
    public function index(Request $request)
    {
        $query = Exercice::with('chapitre')->orderByDesc('id');

        $query->when($request->chapitre_id, fn ($q, $v) => $q->where('chapitre_id', $v));
        $query->when($request->type, fn ($q, $v) => $q->where('type', $v));
        $query->when($request->difficulte, fn ($q, $v) => $q->difficulte((int) $v));
        $query->when($request->annee_origine, fn ($q, $v) => $q->where('annee_origine', $v));
        $query->when($request->boolean('disponibles'), fn ($q) => $q->disponibles());

        // Le corrigé ne peut être chargé que par un admin — jamais par un élève,
        // même s'il force le paramètre with_corrige dans l'URL (section 8.3/9.3).
        if ($request->boolean('with_corrige') && $request->user()->isAdmin()) {
            $query->with('corrige');
        }

        return ExerciceResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    public function store(StoreExerciceRequest $request)
    {
        $exercice = DB::transaction(function () use ($request) {
            $exercice = Exercice::create($request->safe()->except('corrige'));
            $exercice->corrige()->create($request->validated('corrige'));

            return $exercice;
        });

        return (new ExerciceResource($exercice->load('chapitre', 'corrige')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, Exercice $exercice)
    {
        $exercice->load('chapitre');

        if ($request->boolean('with_corrige') && $request->user()->isAdmin()) {
            $exercice->load('corrige');
        }

        return new ExerciceResource($exercice);
    }

    public function update(UpdateExerciceRequest $request, Exercice $exercice)
    {
        DB::transaction(function () use ($request, $exercice) {
            $exercice->update($request->safe()->except('corrige'));

            if ($request->has('corrige')) {
                $exercice->corrige()->updateOrCreate([], $request->validated('corrige'));
            }
        });

        return new ExerciceResource($exercice->load('chapitre', 'corrige'));
    }

    public function destroy(Exercice $exercice)
    {
        $exercice->delete();

        return response()->noContent();
    }
}