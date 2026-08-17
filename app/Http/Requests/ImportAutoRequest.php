<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportAutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // déjà filtré par le middleware role:admin sur la route
    }

    public function rules(): array
    {
        return [
            'fichier' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:20480'],
            'chapitre_id' => ['required', 'exists:chapitres,id'],
            'annee_origine' => ['required', 'integer', 'min:2000', 'max:'.date('Y')],
            'difficulte' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'forcer_vision_ia' => ['sometimes', 'boolean'],
            // false par défaut : les exercices restent en brouillon pour
            // une relecture rapide (l'OCR digitalise un contenu EXISTANT,
            // donc une IA qui se trompe sur le corrigé induirait tous les
            // élèves en erreur — contrairement à la génération pure où le
            // corrigé est produit par la même IA qui a écrit l'énoncé).
            'auto_valider' => ['sometimes', 'boolean'],
        ];
    }
}