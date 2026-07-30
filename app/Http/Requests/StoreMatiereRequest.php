<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMatiereRequest extends FormRequest
{
    /**
     * authorize() répond à la question : "cet utilisateur a-t-il
     * le droit de faire cette action ?" (pas "les données sont-elles
     * valides ?" — ça c'est le rôle de rules()).
     * Pour le MVP on autorise tout utilisateur authentifié ; on
     * affinera avec des rôles/policies plus tard si besoin.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:matieres,code'],
            'description' => ['nullable', 'string'],
        ];
    }
}