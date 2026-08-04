<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapitreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'description' => $this->description,
            'ordre' => $this->ordre,
            'matiere' => new MatiereResource($this->whenLoaded('matiere')),
            'niveau' => new NiveauResource($this->whenLoaded('niveau')),
            'sequence' => new SequenceResource($this->whenLoaded('sequence')),
            // Le champ le plus important pour l'app mobile : elle n'a
            // qu'à lire ce booléen, jamais à comparer des dates elle-même
            'est_debloque' => $this->sequence->estDebloquee(),
        ];
    }
}