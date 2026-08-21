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
    ];

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