<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reserve une route aux administrateurs.
 *
 * C'est le controle des roles evoque dans le memoire : l'authentification
 * verifie QUI est l'utilisateur, ce middleware verifie ce qu'il a le droit
 * de faire. Sans lui, un membre authentifie pourrait appeler les routes
 * d'administration.
 */
class EstAdministrateur
{
    public function handle(Request $request, Closure $next): Response
    {
        $utilisateur = $request->user();

        if (! $utilisateur || ! $utilisateur->estAdministrateur()) {
            return response()->json([
                'message' => "Accès réservé à l'administrateur.",
            ], 403);
        }

        return $next($request);
    }
}
