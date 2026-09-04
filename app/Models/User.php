<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'telephone',
        'password',
        'role',
        'niveau',
        'matiere_id',
        'photo_path',
    ];

    // photo_url n'est PAS une colonne : c'est calculé à la volée à partir de
    // photo_path (chemin relatif stocké en base). On renvoie un chemin
    // RELATIF ("/storage/photos/xxx.jpg"), jamais une URL absolue : une URL
    // absolue dépendrait de APP_URL côté serveur (souvent localhost),
    // injoignable depuis le téléphone — même piège que EXPO_PUBLIC_API_URL.
    // Le mobile complète avec sa propre base d'API déjà fonctionnelle
    // (voir src/services/api.ts, buildAssetUrl).
    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return '/storage/'.$this->photo_path;
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class);
    }

    public function soumissions()
    {
        return $this->hasMany(Soumission::class);
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEleve(): bool
    {
        return $this->role === 'eleve';
    }
}