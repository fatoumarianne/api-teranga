<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReservationResource;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\Ressource;
use App\Models\Utilisateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Demandes de reservation : creation par le membre, traitement par
 * l'administrateur, annulation.
 *
 * C'est ici que se trouve la regle metier centrale du projet : une ressource
 * ne peut pas etre reservee deux fois sur un meme creneau.
 */
class ReservationController extends Controller
{
    /**
     * Liste des reservations.
     *
     * Un membre ne voit que les siennes ; l'administrateur voit tout et peut
     * filtrer par statut et par ressource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $utilisateur = $request->user();

        $reservations = Reservation::query()
            ->with(['ressource', 'membre'])
            ->when($utilisateur->estMembre(), fn ($q) => $q->where('id_membre', $utilisateur->id))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('ressource'), fn ($q) => $q->where('id_ressource', $request->integer('ressource')))
            ->orderByDesc('date_debut')
            ->get();

        return ReservationResource::collection($reservations);
    }

    /**
     * Creneaux occupes, pour alimenter le calendrier des disponibilites.
     *
     * Accessible a tout utilisateur authentifie : savoir qu'un creneau est
     * pris ne revele rien de sensible, et c'est ce qui permet au membre de
     * choisir un creneau libre avant de soumettre sa demande.
     */
    public function calendrier(Request $request): AnonymousResourceCollection
    {
        $reservations = Reservation::query()
            ->with('ressource')
            ->actives()
            ->when($request->filled('ressource'), fn ($q) => $q->where('id_ressource', $request->integer('ressource')))
            ->when($request->filled('debut'), fn ($q) => $q->where('date_fin', '>=', $request->string('debut')))
            ->when($request->filled('fin'), fn ($q) => $q->where('date_debut', '<=', $request->string('fin')))
            ->get();

        return ReservationResource::collection($reservations);
    }

    public function show(Request $request, Reservation $reservation): ReservationResource|JsonResponse
    {
        if ($request->user()->estMembre() && $reservation->id_membre !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        return new ReservationResource($reservation->load(['ressource', 'membre', 'administrateur']));
    }

    /**
     * Nouvelle demande de reservation.
     *
     * La verification du conflit et l'insertion sont faites dans une
     * transaction avec verrou sur la ressource : sans cela, deux demandes
     * simultanees sur le meme creneau pourraient toutes deux passer le test
     * avant que l'une ou l'autre ne soit enregistree.
     */
    public function store(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'id_ressource' => ['required', 'integer', 'exists:ressource,id'],
            'date_debut' => ['required', 'date', 'after:now'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
        ], [
            'date_debut.after' => 'La réservation doit porter sur un créneau à venir.',
            'date_fin.after' => 'La fin du créneau doit suivre son début.',
            'id_ressource.exists' => "Cette ressource n'existe pas.",
        ]);

        $utilisateur = $request->user();

        try {
            $reservation = DB::transaction(function () use ($donnees, $utilisateur) {
                $ressource = Ressource::lockForUpdate()->findOrFail($donnees['id_ressource']);

                if (! $ressource->disponible) {
                    throw new \DomainException("Cette ressource n'est pas disponible à la réservation.");
                }

                if (! $ressource->estLibreSur($donnees['date_debut'], $donnees['date_fin'])) {
                    throw new \DomainException(
                        'Ce créneau est déjà pris pour cette ressource. Choisissez un autre créneau.'
                    );
                }

                $reservation = Reservation::create([
                    'id_membre' => $utilisateur->id,
                    'id_ressource' => $ressource->id,
                    'date_debut' => $donnees['date_debut'],
                    'date_fin' => $donnees['date_fin'],
                    'statut' => Reservation::STATUT_EN_ATTENTE,
                ]);

                // l'administrateur est prevenu de la nouvelle demande
                $libelle = $utilisateur->prenom . ' ' . $utilisateur->nom;
                foreach (Utilisateur::where('role', Utilisateur::ROLE_ADMIN)->pluck('id') as $idAdmin) {
                    Notification::envoyer(
                        $idAdmin,
                        "Nouvelle demande de réservation de {$libelle} pour « {$ressource->nom} »."
                    );
                }

                return $reservation;
            });
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'message' => 'Demande enregistrée. Elle est en attente de validation.',
            'reservation' => new ReservationResource($reservation->load('ressource')),
        ], 201);
    }

    /** Validation d'une demande par l'administrateur. */
    public function valider(Request $request, Reservation $reservation): JsonResponse
    {
        if (! $reservation->estEnAttente()) {
            return response()->json([
                'message' => 'Seule une demande en attente peut être validée.',
            ], 409);
        }

        // un autre creneau a pu etre valide entre-temps
        $ressource = $reservation->ressource;
        if (! $ressource->estLibreSur($reservation->date_debut, $reservation->date_fin, $reservation->id)) {
            return response()->json([
                'message' => 'Un conflit de créneau est apparu sur cette ressource. Proposez un autre créneau au membre.',
            ], 409);
        }

        $reservation->update([
            'statut' => Reservation::STATUT_VALIDEE,
            'id_administrateur' => $request->user()->id,
            'motif' => $request->input('motif'),
        ]);

        Notification::envoyer(
            $reservation->id_membre,
            "Votre réservation de « {$ressource->nom} » a été validée."
        );

        return response()->json([
            'message' => 'Réservation validée.',
            'reservation' => new ReservationResource($reservation->fresh()->load(['ressource', 'membre'])),
        ]);
    }

    /** Refus d'une demande, avec motif obligatoire. */
    public function refuser(Request $request, Reservation $reservation): JsonResponse
    {
        $donnees = $request->validate([
            'motif' => ['required', 'string', 'max:500'],
        ], [
            'motif.required' => 'Un motif est obligatoire pour refuser une demande.',
        ]);

        if (! $reservation->estEnAttente()) {
            return response()->json([
                'message' => 'Seule une demande en attente peut être refusée.',
            ], 409);
        }

        $reservation->update([
            'statut' => Reservation::STATUT_REFUSEE,
            'id_administrateur' => $request->user()->id,
            'motif' => $donnees['motif'],
        ]);

        Notification::envoyer(
            $reservation->id_membre,
            "Votre réservation de « {$reservation->ressource->nom} » a été refusée : {$donnees['motif']}"
        );

        return response()->json([
            'message' => 'Réservation refusée.',
            'reservation' => new ReservationResource($reservation->fresh()->load(['ressource', 'membre'])),
        ]);
    }

    /**
     * Annulation par le membre (ou par l'administrateur).
     *
     * Le creneau redevient alors disponible, puisque la recherche de conflits
     * ignore les reservations annulees.
     */
    public function annuler(Request $request, Reservation $reservation): JsonResponse
    {
        $utilisateur = $request->user();

        if ($utilisateur->estMembre() && $reservation->id_membre !== $utilisateur->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        if (! $reservation->estAnnulable()) {
            return response()->json([
                'message' => "Cette réservation ne peut plus être annulée (créneau passé ou demande déjà traitée).",
            ], 409);
        }

        $reservation->update(['statut' => Reservation::STATUT_ANNULEE]);

        // on previent l'autre partie
        if ($utilisateur->estMembre()) {
            $libelle = $utilisateur->prenom . ' ' . $utilisateur->nom;
            foreach (Utilisateur::where('role', Utilisateur::ROLE_ADMIN)->pluck('id') as $idAdmin) {
                Notification::envoyer(
                    $idAdmin,
                    "{$libelle} a annulé sa réservation de « {$reservation->ressource->nom} »."
                );
            }
        } else {
            Notification::envoyer(
                $reservation->id_membre,
                "Votre réservation de « {$reservation->ressource->nom} » a été annulée par le club."
            );
        }

        return response()->json([
            'message' => 'Réservation annulée.',
            'reservation' => new ReservationResource($reservation->fresh()->load('ressource')),
        ]);
    }
}
