<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChapitreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'matiere_id' => ['sometimes', 'required', 'exists:matieres,id'],
            'niveau_id' => ['sometimes', 'required', 'exists:niveaux,id'],
            'sequence_id' => ['sometimes', 'required', 'exists:sequences,id'],
            'titre' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ];
    }
}