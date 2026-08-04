<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chapitre extends Model
{
    use HasFactory;

    protected $fillable = [
        'matiere_id', 'niveau_id', 'sequence_id', 'titre', 'description', 'ordre',
    ];

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    /**
     * Délègue à la séquence : un chapitre est débloqué si et seulement
     * si sa séquence l'est. Pas de logique de date dupliquée ici.
     */
    public function estDebloque(): bool
    {
        return $this->sequence->estDebloque ?? $this->sequence->estDebloquee();
    }

    public function scopeDebloques($query)
    {
        return $query->whereHas('sequence', fn ($q) => $q->debloquees());
    }
}