<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenererExplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exercice_id' => ['nullable', 'integer'],
            'enonce' => ['required', 'string'],
            'corrige_officiel' => ['required', 'string'],
            'reponse_eleve' => ['nullable', 'string'],
        ];
    }
}