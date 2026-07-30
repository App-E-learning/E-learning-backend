<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Sequence extends Model
{
    use HasFactory;

    protected $fillable = ['niveau_id', 'nom', 'ordre', 'date_debut', 'date_fin'];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    public function chapitres(): HasMany
    {
        return $this->hasMany(Chapitre::class);
    }

    /**
     * Une séquence est débloquée dès que sa date de début est atteinte.
     * C'est la seule source de vérité pour le déblocage : ne pas dupliquer
     * cette règle ailleurs (Chapitre délègue à cette méthode).
     */
    public function estDebloquee(?Carbon $reference = null): bool
    {
        $reference ??= now();
        return $this->date_debut->lessThanOrEqualTo($reference);
    }

    public function scopeDebloquees($query, ?Carbon $reference = null)
    {
        $reference ??= now();
        return $query->where('date_debut', '<=', $reference);
    }
}