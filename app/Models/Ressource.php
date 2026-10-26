<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ressource reservable : une salle ou un equipement sportif.
 */
class Ressource extends Model
{
    use HasFactory;

    protected $table = 'ressource';

    public const TYPE_SALLE = 'salle';
    public const TYPE_EQUIPEMENT = 'equipement';

    protected $fillable = [
        'type',
        'nom',
        'description',
        'image',
        'disponible',
        'capacite',
        'quantite',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'disponible' => 'boolean',
            'capacite' => 'integer',
            'quantite' => 'integer',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'id_ressource');
    }

    /**
     * La ressource est-elle libre sur ce creneau ?
     *
     * Deux creneaux se chevauchent des lors que l'un commence avant que
     * l'autre ne finisse, et reciproquement. Seules les reservations en
     * attente ou validees bloquent un creneau : une reservation refusee ou
     * annulee le libere.
     *
     * @param  int|null  $sauf  reservation a ignorer (cas d'une modification)
     */
    public function estLibreSur(string $debut, string $fin, ?int $sauf = null): bool
    {
        return ! $this->reservations()
            ->whereIn('statut', [Reservation::STATUT_EN_ATTENTE, Reservation::STATUT_VALIDEE])
            ->when($sauf, fn ($q) => $q->where('id', '!=', $sauf))
            ->where('date_debut', '<', $fin)
            ->where('date_fin', '>', $debut)
            ->exists();
    }
}
