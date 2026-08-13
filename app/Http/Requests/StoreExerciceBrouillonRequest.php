<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExerciceBrouillonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // déjà filtré par le middleware role:admin sur la route
    }

    public function rules(): array
    {
        return [
            'chapitre_id' => ['required', 'exists:chapitres,id'],
            'enonce' => ['required', 'string', 'min:5'],
            'type' => ['required', Rule::in(['qcm', 'numerique', 'texte_court'])],
            'difficulte' => ['required', 'integer', 'min:1', 'max:5'],
            'annee_origine' => ['required', 'integer', 'min:2000', 'max:'.date('Y')],
            // Pas de corrigé ici, volontairement : c'est un brouillon à
            // compléter ensuite (voir ExerciceImportController::valider).
            'options' => ['nullable', 'array'],
            'options.*' => ['string'],
        ];
    }
}