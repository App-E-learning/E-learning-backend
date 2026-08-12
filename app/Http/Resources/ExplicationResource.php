<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exercice_id' => $this->exercice_id,
            'corrige_officiel' => $this->corrige_officiel,
            'explication' => $this->contenu,
            'genere_le' => $this->created_at->toIso8601String(),
        ];
    }
}