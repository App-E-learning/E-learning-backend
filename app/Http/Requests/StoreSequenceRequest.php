<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSequenceRequest extends FormRequest
{
    /**
     * NOTE (fix) : ce fichier ne définissait ni authorize() à true ni de
     * règles — POST /sequences renvoyait donc systématiquement 403. Aligné
     * ici sur UpdateSequenceRequest, qui avait la bonne logique.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'niveau_id' => ['required', 'exists:niveaux,id'],
            'nom' => ['required', 'string', 'max:255'],
            'ordre' => [
                'required', 'integer', 'min:1',
                Rule::unique('sequences')->where(fn ($q) => $q->where('niveau_id', $this->niveau_id)),
            ],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after:date_debut'],
        ];
    }
}