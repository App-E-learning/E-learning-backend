<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'niveau_id' => ['sometimes', 'required', 'exists:niveaux,id'],
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'ordre' => [
                'sometimes', 'required', 'integer', 'min:1',
                Rule::unique('sequences')
                    ->where(fn ($q) => $q->where('niveau_id', $this->niveau_id ?? $this->route('sequence')->niveau_id))
                    ->ignore($this->route('sequence')),
            ],
            'date_debut' => ['sometimes', 'required', 'date'],
            'date_fin' => ['nullable', 'date', 'after:date_debut'],
        ];
    }
}