<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProposerCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // déjà filtré par le middleware role:admin sur la route
    }

    public function rules(): array
    {
        return [
            'enonce' => ['required', 'string', 'min:5'],
            'type' => ['required', Rule::in(['qcm', 'numerique', 'texte_court'])],
            'options' => ['array'],
            'options.*' => ['string'],
        ];
    }
}