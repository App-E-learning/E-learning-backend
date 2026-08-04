<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SequenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'niveau_id' => $this->niveau_id,
            'nom' => $this->nom,
            'ordre' => $this->ordre,
            'date_debut' => $this->date_debut->toDateString(),
            'date_fin' => $this->date_fin?->toDateString(),
            // On expose directement le résultat du calcul métier :
            // le front (Flutter) n'a jamais à recalculer une date lui-même
            'est_debloquee' => $this->estDebloquee(),
            // whenLoaded : n'inclut la relation QUE si elle a été chargée
            // avec ->with('niveau') en amont. Évite une requête SQL en plus
            // si on n'a pas besoin du détail du niveau.
            'niveau' => new NiveauResource($this->whenLoaded('niveau')),
        ];
    }
}