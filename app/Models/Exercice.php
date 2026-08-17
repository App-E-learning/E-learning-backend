<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Exercice extends Model
{
    use HasFactory;

    protected $fillable = [
    'chapitre_id',
    'enonce',
    'image_url',
    'type',
    'options',
    'difficulte',
    'annee_origine',
    'origine',
    'statut',
    'texte_ocr_brut',
        ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'annee_origine' => 'integer',
        ];
    }

    public function chapitre(): BelongsTo
    {
        return $this->belongsTo(Chapitre::class);
    }

    public function corrige(): HasOne
    {
        return $this->hasOne(Corrige::class);
    }

    public function soumissions()
    {
        return $this->hasMany(Soumission::class);
    }

    /**
     * Filtrage: uniquement les exercices dont le chapitre est débloqué.
     * Délègue à Sequence::scopeDebloquees(), déjà en place dans le projet
     * — pas de logique de date dupliquée ici (section 8.3).
     */
/**
     * Filtrage: chapitre débloqué ET exercice validé (jamais un brouillon
     * importé par OCR tant qu'un admin ne l'a pas relu). Section 8.3.
     * Délègue à Sequence::scopeDebloquees(), déjà en place dans le projet
     * — pas de logique de date dupliquée ici.
     */
public function scopeDisponibles($query)
    {
return $query
->where('statut', 'valide')
->whereHas('chapitre.sequence', fn ($q) => $q->debloquees());
    }

public function scopeDifficulte($query, int $niveau)
    {
return $query->where('difficulte', $niveau);
    }

public function scopeBrouillons($query)
    {
return $query->where('statut', 'brouillon');
    }

}