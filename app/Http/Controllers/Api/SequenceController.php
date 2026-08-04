<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSequenceRequest;
use App\Http\Requests\UpdateSequenceRequest;
use App\Http\Resources\SequenceResource;
use App\Models\Sequence;
use Illuminate\Http\Response;

class SequenceController extends Controller
{
    public function index()
    {
        // with('niveau') précharge la relation en 1 seule requête SQL
        // (JOIN interne), au lieu de N requêtes en plus pendant que
        // la Resource accède à ->niveau pour chaque séquence
        return SequenceResource::collection(Sequence::with('niveau')->orderBy('ordre')->get());
    }

    public function store(StoreSequenceRequest $request)
    {
        $sequence = Sequence::create($request->validated());

        return (new SequenceResource($sequence))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Sequence $sequence)
    {
        return new SequenceResource($sequence->load('niveau'));
    }

    public function update(UpdateSequenceRequest $request, Sequence $sequence)
    {
        $sequence->update($request->validated());

        return new SequenceResource($sequence);
    }

    public function destroy(Sequence $sequence)
    {
        $sequence->delete();

        return response()->noContent();
    }
}