<?php

namespace App\Http\Controllers;

use App\Http\Resources\RessourceResource;
use App\Models\Ressource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Gestion des salles et des equipements.
 *
 * La consultation est ouverte a tout utilisateur authentifie ; la creation,
 * la modification et la suppression sont reservees a l'administrateur
 * (voir les routes et le middleware « admin »).
 */
class RessourceController extends Controller
{
    /** Liste des ressources, filtrable par type et par disponibilite. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $ressources = Ressource::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('disponible'), fn ($q) => $q->where('disponible', $request->boolean('disponible')))
            ->orderBy('type')
            ->orderBy('nom')
            ->get();

        return RessourceResource::collection($ressources);
    }

    /**
     * Liste publique, affichee sur la page d'accueil.
     *
     * Elle ne demande pas d'authentification : un visiteur doit pouvoir voir
     * ce que le club propose avant de creer un compte. Seules les ressources
     * disponibles sont renvoyees.
     */
    public function publiques(): AnonymousResourceCollection
    {
        return RessourceResource::collection(
            Ressource::where('disponible', true)
                ->orderBy('type')
                ->orderBy('nom')
                ->get()
        );
    }

    public function show(Ressource $ressource): RessourceResource
    {
        return new RessourceResource($ressource);
    }

    public function store(Request $request): JsonResponse
    {
        $ressource = Ressource::create($this->valider($request));

        return response()->json([
            'message' => 'Ressource créée.',
            'ressource' => new RessourceResource($ressource),
        ], 201);
    }

    public function update(Request $request, Ressource $ressource): JsonResponse
    {
        $ressource->update($this->valider($request, partiel: true));

        return response()->json([
            'message' => 'Ressource mise à jour.',
            'ressource' => new RessourceResource($ressource->fresh()),
        ]);
    }

    /**
     * Suppression d'une ressource.
     *
     * Refusee si des reservations y sont rattachees : la contrainte de cle
     * etrangere l'interdit de toute facon, mais on renvoie ici un message
     * clair plutot qu'une erreur de base de donnees.
     */
    public function destroy(Ressource $ressource): JsonResponse
    {
        if ($ressource->reservations()->exists()) {
            return response()->json([
                'message' => "Cette ressource ne peut pas être supprimée : des réservations y sont rattachées. "
                    . "Vous pouvez la rendre indisponible à la place.",
            ], 409);
        }

        $ressource->delete();

        return response()->json(['message' => 'Ressource supprimée.']);
    }

    /** Regles de validation, communes a la creation et a la modification. */
    private function valider(Request $request, bool $partiel = false): array
    {
        $requis = $partiel ? 'sometimes' : 'required';

        return $request->validate([
            'type' => [$requis, Rule::in([Ressource::TYPE_SALLE, Ressource::TYPE_EQUIPEMENT])],
            'nom' => [$requis, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:500'],
            'disponible' => ['sometimes', 'boolean'],
            'capacite' => ['nullable', 'integer', 'min:1'],
            'quantite' => ['nullable', 'integer', 'min:0'],
            'etat' => ['nullable', 'string', 'max:255'],
        ], [
            'type.in' => "Le type doit être « salle » ou « equipement ».",
        ]);
    }
}
