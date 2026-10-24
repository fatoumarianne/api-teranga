<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mise en forme d'un utilisateur pour l'API.
 *
 * Le mot de passe n'apparait jamais : il est deja masque par $hidden sur le
 * modele, et cette ressource ne liste que les champs voulus.
 */
class UtilisateurResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'nom_complet' => trim($this->prenom . ' ' . $this->nom),
            'email' => $this->email,
            'telephone' => $this->telephone,
            'role' => $this->role,
            'statut' => $this->statut,
            'date_inscription' => $this->date_inscription?->toIso8601String(),
        ];
    }
}
