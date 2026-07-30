<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // protégé par auth:sanctum au niveau des routes
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'telephone' => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('users', 'telephone')->ignore($userId)],
            'matiere_id' => ['sometimes', 'exists:matieres,id'],
        ];
    }
}