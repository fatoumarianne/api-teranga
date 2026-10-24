<?php

namespace App\Http\Controllers;

use App\Http\Resources\UtilisateurResource;
use App\Models\Utilisateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Gestion des comptes des membres par l'administrateur.
 *
 * Un compte n'est jamais supprime : il est desactive, pour conserver
 * l'historique des reservations qui lui sont rattachees.
 */
class MembreController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $membres = Utilisateur::query()
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('recherche'), function ($q) use ($request) {
                $terme = '%' . $request->string('recherche') . '%';
                $q->where(fn ($s) => $s->where('nom', 'ilike', $terme)
                    ->orWhere('prenom', 'ilike', $terme)
                    ->orWhere('email', 'ilike', $terme));
            })
            ->orderBy('nom')
            ->get();

        return UtilisateurResource::collection($membres);
    }

    public function show(Utilisateur $membre): UtilisateurResource
    {
        return new UtilisateurResource($membre->loadCount('reservations'));
    }

    /** Creation d'un compte par l'administrateur (membre ou administrateur). */
    public function store(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:utilisateur,email'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'mot_de_passe' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in([Utilisateur::ROLE_MEMBRE, Utilisateur::ROLE_ADMIN])],
        ]);

        $donnees['statut'] = 'actif';
        $membre = Utilisateur::create($donnees);

        return response()->json([
            'message' => 'Compte créé.',
            'membre' => new UtilisateurResource($membre),
        ], 201);
    }

    public function update(Request $request, Utilisateur $membre): JsonResponse
    {
        $donnees = $request->validate([
            'nom' => ['sometimes', 'string', 'max:255'],
            'prenom' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('utilisateur', 'email')->ignore($membre->id)],
            'telephone' => ['nullable', 'string', 'max:30'],
            'role' => ['sometimes', Rule::in([Utilisateur::ROLE_MEMBRE, Utilisateur::ROLE_ADMIN])],
            'statut' => ['sometimes', Rule::in(['actif', 'inactif'])],
        ]);

        $membre->update($donnees);

        return response()->json([
            'message' => 'Compte mis à jour.',
            'membre' => new UtilisateurResource($membre->fresh()),
        ]);
    }

    /** Activation / desactivation d'un compte. */
    public function basculerStatut(Request $request, Utilisateur $membre): JsonResponse
    {
        if ($membre->id === $request->user()->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas désactiver votre propre compte.',
            ], 409);
        }

        $membre->update(['statut' => $membre->estActif() ? 'inactif' : 'actif']);

        // un compte desactive ne doit plus pouvoir utiliser ses jetons
        if (! $membre->estActif()) {
            $membre->tokens()->delete();
        }

        return response()->json([
            'message' => $membre->estActif() ? 'Compte activé.' : 'Compte désactivé.',
            'membre' => new UtilisateurResource($membre->fresh()),
        ]);
    }
}
