<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Corrige extends Model
{
    use HasFactory;

    protected $fillable = [
        'exercice_id',
        'reponses_correctes',
        'explication_officielle',
        'bareme',
    ];

    protected function casts(): array
    {
        return [
            'reponses_correctes' => 'array',
            'bareme' => 'decimal:2',
        ];
    }

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(Exercice::class);
    }
}