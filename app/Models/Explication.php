<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Explication extends Model
{
    use HasFactory;

    protected $fillable = [
        'exercice_id', 'cle_cache', 'enonce', 'corrige_officiel',
        'reponse_eleve', 'contenu', 'modele_ia', 'tokens_utilises',
    ];
}