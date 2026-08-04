<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExerciceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chapitre_id' => $this->chapitre_id,
            'chapitre' => new ChapitreResource($this->whenLoaded('chapitre')),
            'enonce' => $this->enonce,
            'image_url' => $this->image_url,
            'type' => $this->type,
            'options' => $this->options,
            'difficulte' => $this->difficulte,
            'annee_origine' => $this->annee_origine,

            // Le corrigé n'est inclus QUE si explicitement demandé
            // (back-office admin) — jamais exposé par défaut à l'élève
            // avant qu'il ait soumis sa réponse (section 8.3 et 9.3/9.4).
            'corrige' => $this->when(
                $request->boolean('with_corrige') && $this->relationLoaded('corrige'),
                fn () => new CorrigeResource($this->corrige)
            ),
        ];
    }
}