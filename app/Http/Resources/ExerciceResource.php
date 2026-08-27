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

            // Simple indicateur booléen (jamais le contenu de la réponse) —
            // utilisé par la liste des brouillons pour savoir si le bouton
            // "Valider" peut être activé, sans avoir à demander with_corrige=1.
            'corrige_pret' => $this->when(
                $this->relationLoaded('corrige'),
                fn () => ! empty($this->corrige?->reponses_correctes)
            ),

            // Présent uniquement pour un élève (voir ExerciceController::index) :
            // permet à l'app de reprendre l'entraînement au premier exercice
            // pas encore réussi, plutôt que de toujours recommencer à 1.
            'deja_reussi' => $this->when(isset($this->deja_reussi), fn () => $this->deja_reussi),
        ];
    }
}