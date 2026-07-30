<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChapitreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'matiere_id' => ['required', 'exists:matieres,id'],
            'niveau_id' => ['required', 'exists:niveaux,id'],
            'sequence_id' => ['required', 'exists:sequences,id'],
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ];
    }
}