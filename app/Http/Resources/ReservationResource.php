<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mise en forme d'une reservation.
 *
 * Le format des dates suit ce qu'attend FullCalendar cote Angular : les
 * champs « start », « end » et « color » permettent d'alimenter directement
 * le calendrier des disponibilites sans transformation supplementaire.
 */
class ReservationResource extends JsonResource
{
    /** Couleur d'affichage dans le calendrier selon le statut. */
    private const COULEURS = [
        'validee' => '#198754',     // vert
        'en_attente' => '#ffc107',  // orange
        'refusee' => '#dc3545',     // rouge
        'annulee' => '#6c757d',     // gris
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'statut' => $this->statut,
            'motif' => $this->motif,

            'date_demande' => $this->date_demande?->toIso8601String(),
            'date_debut' => $this->date_debut->toIso8601String(),
            'date_fin' => $this->date_fin->toIso8601String(),

            // champs consommes tels quels par FullCalendar
            'title' => $this->whenLoaded('ressource', fn () => $this->ressource->nom),
            'start' => $this->date_debut->toIso8601String(),
            'end' => $this->date_fin->toIso8601String(),
            'color' => self::COULEURS[$this->statut] ?? '#6c757d',

            'ressource' => new RessourceResource($this->whenLoaded('ressource')),
            'membre' => new UtilisateurResource($this->whenLoaded('membre')),
            'administrateur' => new UtilisateurResource($this->whenLoaded('administrateur')),

            'annulable' => $this->estAnnulable(),
        ];
    }
}
