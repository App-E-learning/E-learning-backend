<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNiveauRequest;
use App\Http\Requests\UpdateNiveauRequest;
use App\Http\Resources\NiveauResource;
use App\Models\Niveau;
use Illuminate\Http\Response;

class NiveauController extends Controller
{
    public function index()
    {
        return NiveauResource::collection(Niveau::all());
    }

    public function store(StoreNiveauRequest $request)
    {
        $niveau = Niveau::create($request->validated());

        return (new NiveauResource($niveau))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Niveau $niveau)
    {
        return new NiveauResource($niveau);
    }

    public function update(UpdateNiveauRequest $request, Niveau $niveau)
    {
        $niveau->update($request->validated());

        return new NiveauResource($niveau);
    }

    public function destroy(Niveau $niveau)
    {
        $niveau->delete();

        return response()->noContent();
    }
}