<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route publique, pas de check d'autorisation
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'required_without:telephone', 'email', 'max:255', 'unique:users,email'],
            'telephone' => ['nullable', 'required_without:email', 'string', 'max:20', 'unique:users,telephone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'niveau' => ['required', 'string', Rule::in(['Terminale C'])], // MVP: un seul niveau
            'matiere_id' => ['required', 'exists:matieres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required_without' => 'Un email ou un numéro de téléphone est requis.',
            'telephone.required_without' => 'Un email ou un numéro de téléphone est requis.',
            'niveau.in' => 'Seul le niveau Terminale C est disponible pour le moment.',
        ];
    }
}