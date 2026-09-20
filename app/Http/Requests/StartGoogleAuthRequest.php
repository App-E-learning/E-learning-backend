<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartGoogleAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route publique
    }

    public function rules(): array
    {
        return [
            // Optionnel : seulement nécessaire si c'est une création de
            // compte (aucun compte existant avec cet email/google_id).
            'matiere_id' => ['sometimes', 'integer', 'exists:matieres,id'],
        ];
    }
}