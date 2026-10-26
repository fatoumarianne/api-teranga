<?php

namespace App\Http\Controllers;

use App\Http\Resources\UtilisateurResource;
use App\Models\Utilisateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Inscription, authentification et deconnexion.
 *
 * L'API etant sans etat, l'authentification repose sur un jeton Sanctum que
 * le frontend Angular renvoie ensuite dans l'en-tete Authorization.
 */
class AuthController extends Controller
{
    /** Inscription d'un nouveau membre. */
    public function inscription(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:utilisateur,email'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'mot_de_passe' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'mot_de_passe.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'mot_de_passe.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        // le role n'est jamais accepte depuis la requete : on ne veut pas
        // qu'un visiteur puisse se declarer administrateur
        $donnees['role'] = Utilisateur::ROLE_MEMBRE;
        $donnees['statut'] = 'actif';

        $utilisateur = Utilisateur::create($donnees);

        return response()->json([
            'message' => 'Inscription réussie.',
            'utilisateur' => new UtilisateurResource($utilisateur),
            'token' => $utilisateur->createToken('api')->plainTextToken,
        ], 201);
    }

    /** Connexion : renvoie un jeton si les identifiants sont valides. */
    public function connexion(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'email' => ['required', 'email'],
            'mot_de_passe' => ['required', 'string'],
        ]);

        $utilisateur = Utilisateur::where('email', $donnees['email'])->first();

        if (! $utilisateur || ! Hash::check($donnees['mot_de_passe'], $utilisateur->mot_de_passe)) {
            // message volontairement identique dans les deux cas : indiquer
            // qu'un compte existe permettrait d'enumerer les adresses
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        if (! $utilisateur->estActif()) {
            return response()->json([
                'message' => 'Ce compte est désactivé. Contactez le club.',
            ], 403);
        }

        return response()->json([
            'message' => 'Connexion réussie.',
            'utilisateur' => new UtilisateurResource($utilisateur),
            'token' => $utilisateur->createToken('api')->plainTextToken,
        ]);
    }

    /** Profil de l'utilisateur authentifie. */
    public function profil(Request $request): UtilisateurResource
    {
        return new UtilisateurResource($request->user());
    }

    /**
     * Mise a jour du profil par l'utilisateur lui-meme.
     *
     * Le changement de mot de passe exige le mot de passe actuel : sans cette
     * verification, quiconque mettrait la main sur une session ouverte
     * pourrait s'approprier le compte definitivement.
     */
    public function majProfil(Request $request): JsonResponse
    {
        $utilisateur = $request->user();

        $donnees = $request->validate([
            'nom' => ['sometimes', 'string', 'max:255'],
            'prenom' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255',
                        'unique:utilisateur,email,' . $utilisateur->id],
            'telephone' => ['nullable', 'string', 'max:30'],
            'mot_de_passe_actuel' => ['required_with:mot_de_passe', 'string'],
            'mot_de_passe' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'mot_de_passe_actuel.required_with' => 'Le mot de passe actuel est obligatoire.',
            'mot_de_passe.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'mot_de_passe.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
        ]);

        if (isset($donnees['mot_de_passe'])) {
            if (! Hash::check($donnees['mot_de_passe_actuel'], $utilisateur->mot_de_passe)) {
                throw ValidationException::withMessages([
                    'mot_de_passe_actuel' => ['Le mot de passe actuel est incorrect.'],
                ]);
            }
        }

        // le mot de passe actuel sert seulement a la verification : il n'est
        // pas une colonne de la table
        unset($donnees['mot_de_passe_actuel']);

        $utilisateur->update($donnees);

        return response()->json([
            'message' => 'Profil mis à jour.',
            'utilisateur' => new UtilisateurResource($utilisateur->fresh()),
        ]);
    }

    /** Deconnexion : revoque le jeton courant uniquement. */
    public function deconnexion(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }
}
