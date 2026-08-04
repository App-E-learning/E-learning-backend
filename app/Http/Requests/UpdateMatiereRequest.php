<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMatiereRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes', 'required', 'string', 'max:20',
                // Rule::unique ignore l'enregistrement courant, sinon
                // on ne pourrait jamais modifier une matière sans
                // changer son code (il se verrait "déjà pris" par lui-même)
                Rule::unique('matieres', 'code')->ignore($this->route('matiere')),
            ],
            'description' => ['nullable', 'string'],
        ];
    }
}