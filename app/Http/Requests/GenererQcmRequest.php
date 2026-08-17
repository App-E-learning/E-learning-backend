<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenererQcmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // déjà filtré par le middleware role:admin sur la route
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'integer', 'min:1', 'max:15'],
            'difficulte_depart' => ['sometimes', 'integer', 'min:1', 'max:5'],
            // true par défaut : les exercices générés sont immédiatement
            // visibles aux élèves. Passer false pour les créer en brouillon
            // et les faire relire par un admin avant publication.
            'auto_valider' => ['sometimes', 'boolean'],
        ];
    }
}