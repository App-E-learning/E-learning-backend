<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matiere extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'code', 'description'];

    public function chapitres(): HasMany
    {
        return $this->hasMany(Chapitre::class);
    }
}