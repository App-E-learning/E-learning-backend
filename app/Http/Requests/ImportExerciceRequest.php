<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportExerciceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'fichier' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:20480'],
            'chapitre_id' => ['required', 'exists:chapitres,id'],
            'type' => ['required', Rule::in(['qcm', 'numerique', 'texte_court'])],
            'difficulte' => ['required', 'integer', 'min:1', 'max:5'],
            'annee_origine' => ['required', 'integer', 'min:2000', 'max:' . date('Y')],
            // Permet de forcer directement le passage par GPT-4o vision,
            // utile pour les scans qu'on sait difficiles pour Tesseract
            // (mauvaise qualité, écriture serrée, manuscrite...).
            'forcer_vision_ia' => ['sometimes', 'boolean'],
        ];
    }
}