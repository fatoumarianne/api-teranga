<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Ressource;
use App\Models\Utilisateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Statistiques du tableau de bord de l'administrateur.
 *
 * Les series renvoyees ici alimentent directement les graphiques Chart.js
 * cote Angular.
 */
class StatistiqueController extends Controller
{
    public function tableauDeBord(Request $request): JsonResponse
    {
        $debut = $request->date('debut') ?? now()->subMonths(6)->startOfMonth();
        $fin = $request->date('fin') ?? now()->endOfMonth();

        return response()->json([
            'compteurs' => [
                'membres' => Utilisateur::where('role', Utilisateur::ROLE_MEMBRE)->count(),
                'ressources' => Ressource::count(),
                'salles' => Ressource::where('type', Ressource::TYPE_SALLE)->count(),
                'equipements' => Ressource::where('type', Ressource::TYPE_EQUIPEMENT)->count(),
                'en_attente' => Reservation::enAttente()->count(),
                'validees' => Reservation::where('statut', Reservation::STATUT_VALIDEE)->count(),
            ],

            'par_statut' => Reservation::query()
                ->select('statut', DB::raw('count(*) as total'))
                ->groupBy('statut')
                ->pluck('total', 'statut'),

            'par_ressource' => Reservation::query()
                ->join('ressource', 'ressource.id', '=', 'reservation.id_ressource')
                ->whereBetween('reservation.date_debut', [$debut, $fin])
                ->select('ressource.nom', DB::raw('count(*) as total'))
                ->groupBy('ressource.nom')
                ->orderByDesc('total')
                ->pluck('total', 'nom'),

            'par_mois' => Reservation::query()
                ->whereBetween('date_debut', [$debut, $fin])
                ->select(
                    DB::raw("to_char(date_debut, 'YYYY-MM') as mois"),
                    DB::raw('count(*) as total')
                )
                ->groupBy('mois')
                ->orderBy('mois')
                ->pluck('total', 'mois'),

            'periode' => [
                'debut' => $debut->toDateString(),
                'fin' => $fin->toDateString(),
            ],
        ]);
    }
}
