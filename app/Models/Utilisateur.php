<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Utilisateur de l'application : membre ou administrateur.
 *
 * La table et les colonnes portent les noms du memoire. Comme le mot de passe
 * est stocke dans « mot_de_passe » et non dans « password », on redefinit
 * getAuthPassword() pour que l'authentification de Laravel le trouve.
 */
class Utilisateur extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'utilisateur';

    public const ROLE_MEMBRE = 'membre';
    public const ROLE_ADMIN = 'administrateur';

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'mot_de_passe',
        'role',
        'statut',
    ];

    protected $hidden = [
        'mot_de_passe',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'mot_de_passe' => 'hashed',
            'date_inscription' => 'datetime',
        ];
    }

    /** Laravel cherche « password » par defaut : on le renvoie vers notre colonne. */
    public function getAuthPassword(): string
    {
        return $this->mot_de_passe;
    }

    // ------------------------------------------------------------ relations

    /** Les reservations effectuees par ce membre. */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'id_membre');
    }

    /** Les reservations traitees par cet administrateur. */
    public function reservationsTraitees(): HasMany
    {
        return $this->hasMany(Reservation::class, 'id_administrateur');
    }

    /** Les notifications recues par cet utilisateur. */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'id_utilisateur');
    }

    // --------------------------------------------------------------- roles

    public function estAdministrateur(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function estMembre(): bool
    {
        return $this->role === self::ROLE_MEMBRE;
    }

    public function estActif(): bool
    {
        return $this->statut === 'actif';
    }
}
