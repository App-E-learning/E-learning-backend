<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChapitreRequest;
use App\Http\Requests\UpdateChapitreRequest;
use App\Http\Resources\ChapitreResource;
use App\Models\Chapitre;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ChapitreController extends Controller
{
    public function index(Request $request)
    {
        $query = Chapitre::with(['matiere', 'niveau', 'sequence'])->orderBy('ordre');

        // Filtres optionnels utiles pour l'app mobile plus tard :
        // GET /chapitres?matiere_id=1&niveau_id=1
        $query->when($request->matiere_id, fn ($q, $v) => $q->where('matiere_id', $v));
        $query->when($request->niveau_id, fn ($q, $v) => $q->where('niveau_id', $v));

        // GET /chapitres?debloques=1 -> ne renvoie que les chapitres
        // déjà accessibles à la date du jour. C'est CETTE ligne qui
        // sera utilisée par l'app mobile pour la boucle d'exercices.
        $query->when($request->boolean('debloques'), fn ($q) => $q->debloques());

        return ChapitreResource::collection($query->get());
    }

    public function store(StoreChapitreRequest $request)
    {
        $chapitre = Chapitre::create($request->validated());

        return (new ChapitreResource($chapitre->load(['matiere', 'niveau', 'sequence'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Chapitre $chapitre)
    {
        return new ChapitreResource($chapitre->load(['matiere', 'niveau', 'sequence']));
    }

    public function update(UpdateChapitreRequest $request, Chapitre $chapitre)
    {
        $chapitre->update($request->validated());

        return new ChapitreResource($chapitre->load(['matiere', 'niveau', 'sequence']));
    }

    public function destroy(Chapitre $chapitre)
    {
        $chapitre->delete();

        return response()->noContent();
    }
}