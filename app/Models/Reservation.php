<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande de reservation d'une ressource par un membre.
 *
 * Cycle de vie : en_attente -> validee | refusee, ou en_attente -> annulee.
 * Une reservation validee peut encore etre annulee par le membre tant que le
 * creneau n'a pas commence.
 */
class Reservation extends Model
{
    use HasFactory;

    protected $table = 'reservation';

    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_VALIDEE = 'validee';
    public const STATUT_REFUSEE = 'refusee';
    public const STATUT_ANNULEE = 'annulee';

    protected $fillable = [
        'id_membre',
        'id_ressource',
        'id_administrateur',
        'date_debut',
        'date_fin',
        'statut',
        'motif',
    ];

    protected function casts(): array
    {
        return [
            'date_demande' => 'datetime',
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
        ];
    }

    // ------------------------------------------------------------ relations

    public function membre(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_membre');
    }

    public function ressource(): BelongsTo
    {
        return $this->belongsTo(Ressource::class, 'id_ressource');
    }

    public function administrateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_administrateur');
    }

    // --------------------------------------------------------------- scopes

    public function scopeEnAttente(Builder $q): Builder
    {
        return $q->where('statut', self::STATUT_EN_ATTENTE);
    }

    /** Reservations qui occupent reellement un creneau. */
    public function scopeActives(Builder $q): Builder
    {
        return $q->whereIn('statut', [self::STATUT_EN_ATTENTE, self::STATUT_VALIDEE]);
    }

    /** Reservations chevauchant le creneau donne. */
    public function scopeSurCreneau(Builder $q, string $debut, string $fin): Builder
    {
        return $q->where('date_debut', '<', $fin)->where('date_fin', '>', $debut);
    }

    // ----------------------------------------------------------- transitions

    public function estEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    /**
     * Une reservation est annulable tant qu'elle n'est pas deja annulee ou
     * refusee, et que le creneau n'a pas commence.
     */
    public function estAnnulable(): bool
    {
        return in_array($this->statut, [self::STATUT_EN_ATTENTE, self::STATUT_VALIDEE], true)
            && $this->date_debut->isFuture();
    }
}
