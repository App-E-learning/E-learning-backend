<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CorrigeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'reponses_correctes' => $this->reponses_correctes,
            'explication_officielle' => $this->explication_officielle,
            'bareme' => $this->bareme,
        ];
    }
}