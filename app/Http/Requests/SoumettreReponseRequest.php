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
            // La réponse peut être une chaîne (qcm à choix unique, numérique,
            // texte court) ou un tableau (qcm à choix multiples) — on reste
            // permissif ici et on normalise dans le contrôleur.
            'reponse' => ['required'],
        ];
    }
}