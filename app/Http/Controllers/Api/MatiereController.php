<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMatiereRequest;
use App\Http\Requests\UpdateMatiereRequest;
use App\Http\Resources\MatiereResource;
use App\Models\Matiere;
use Illuminate\Http\Response;

class MatiereController extends Controller
{
    public function index()
    {
        return MatiereResource::collection(Matiere::all());
    }

    public function store(StoreMatiereRequest $request)
    {
        // validated() ne retourne QUE les champs passés dans rules() —
        // même si le client envoie des champs en plus dans le JSON,
        // ils sont ignorés. C'est une protection contre l'injection
        // de champs non désirés (mass assignment).
        $matiere = Matiere::create($request->validated());

        return (new MatiereResource($matiere))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED); // 201, pas 200
    }

    public function show(Matiere $matiere)
    {
        return new MatiereResource($matiere);
    }

    public function update(UpdateMatiereRequest $request, Matiere $matiere)
    {
        $matiere->update($request->validated());

        return new MatiereResource($matiere);
    }

    public function destroy(Matiere $matiere)
    {
        $matiere->delete();

        return response()->noContent(); // 204
    }
}