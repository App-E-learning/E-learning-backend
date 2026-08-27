<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExerciceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'chapitre_id' => ['required', 'exists:chapitres,id'],
            'enonce' => ['required', 'string'],
            'image_url' => ['nullable', 'url'],
            'type' => ['required', Rule::in(['qcm', 'numerique', 'texte_court'])],
            'options' => ['required_if:type,qcm', 'array', 'min:2'],
            'options.*' => ['string'],
            'difficulte' => ['required', 'integer', 'min:1', 'max:5'],
            'annee_origine' => ['required', 'integer', 'min:2000', 'max:' . date('Y')],

            // Corrigé associé — peut être complété plus tard (brouillon en
            // cours de relecture) ; seule la publication (voir
            // ExerciceImportController::valider) exige un corrigé complet.
            'corrige' => ['sometimes', 'array'],
            'corrige.reponses_correctes' => ['sometimes', 'array'],
            'corrige.reponses_correctes.*' => ['string'],
            'corrige.explication_officielle' => ['nullable', 'string'],
            'corrige.bareme' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'options.required_if' => 'Les options sont requises pour un exercice de type QCM.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('type') === 'qcm') {
                $options = $this->input('options', []);
                $reponses = $this->input('corrige.reponses_correctes', []);

                foreach ($reponses as $reponse) {
                    if (! in_array($reponse, $options, true)) {
                        $validator->errors()->add(
                            'corrige.reponses_correctes',
                            "La réponse '{$reponse}' ne fait pas partie des options proposées."
                        );
                    }
                }
            }
        });
    }
}