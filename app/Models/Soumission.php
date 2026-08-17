<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Soumission extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'exercice_id', 'reponse', 'correct'];

    protected function casts(): array
    {
        return [
            'reponse' => 'array',
            'correct' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(Exercice::class);
    }
}