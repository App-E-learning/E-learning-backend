<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SoumettreReponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // le contrôle de rôle est déjà fait par le middleware 'role:eleve' sur la route
    }

    public function rules(): array
    {
        return [
            // Optionnel : un exercice rédigé à plusieurs sous-questions
            // (a, b, c...) n'a pas de réponse unique comparable — l'élève
            // peut simplement demander le corrigé sans avoir soumis de
            // réponse (auto-évaluation sur papier). Voir
            // ExerciceSoumissionController::estCorrecte, qui renvoie alors
            // correct = null plutôt que de forcer un verdict vrai/faux.
            'reponse' => ['sometimes', 'nullable'],
        ];
    }
}