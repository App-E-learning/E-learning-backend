<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Niveau extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'code'];

    public function sequences(): HasMany
    {
        return $this->hasMany(Sequence::class);
    }

    public function chapitres(): HasMany
    {
        return $this->hasMany(Chapitre::class);
    }
}