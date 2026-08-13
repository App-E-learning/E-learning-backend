<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecouperExercicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // déjà filtré par le middleware role:admin sur la route
    }

    public function rules(): array
    {
        return [
            'texte' => ['required', 'string', 'min:20'],
        ];
    }
}