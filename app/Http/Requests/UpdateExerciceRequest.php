<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExerciceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'chapitre_id' => ['sometimes', 'exists:chapitres,id'],
            'enonce' => ['sometimes', 'string'],
            'image_url' => ['sometimes', 'nullable', 'url'],
            'type' => ['sometimes', Rule::in(['qcm', 'numerique', 'texte_court'])],
            'options' => ['sometimes', 'array', 'min:2'],
            'options.*' => ['string'],
            'difficulte' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'annee_origine' => ['sometimes', 'integer', 'min:2000', 'max:' . date('Y')],

            'corrige' => ['sometimes', 'array'],
            'corrige.reponses_correctes' => ['sometimes', 'array', 'min:1'],
            'corrige.reponses_correctes.*' => ['string'],
            'corrige.explication_officielle' => ['nullable', 'string'],
            'corrige.bareme' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}