<?php

namespace App\Http\Resources;

use App\Models\Ressource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mise en forme d'une salle ou d'un equipement.
 *
 * Les champs propres a chaque type ne sont renvoyes que lorsqu'ils ont un
 * sens : capacite pour une salle, quantite et etat pour un equipement.
 */
class RessourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'nom' => $this->nom,
            'description' => $this->description,
            'image' => $this->image,
            'disponible' => $this->disponible,

            $this->mergeWhen($this->type === Ressource::TYPE_SALLE, [
                'capacite' => $this->capacite,
            ]),

            $this->mergeWhen($this->type === Ressource::TYPE_EQUIPEMENT, [
                'quantite' => $this->quantite,
                'etat' => $this->etat,
            ]),
        ];
    }
}
