<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\Reservation;
use App\Models\Ressource;
use App\Models\Utilisateur;
use Illuminate\Database\Seeder;

/**
 * Jeu de donnees de demonstration du Club Sportif Teranga.
 *
 * Il sert a deux choses : tester l'API sans saisie manuelle, et disposer
 * d'un contenu realiste pour les captures d'ecran du memoire.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Le seeder est lance a chaque demarrage du serveur en production.
        // S'il y a deja des utilisateurs, la base a ete remplie une premiere
        // fois : on s'arrete la, sinon les donnees seraient dupliquees.
        if (Utilisateur::count() > 0) {
            $this->command->info('Donnees deja presentes : rien a inserer.');

            return;
        }

        // ------------------------------------------------- utilisateurs

        $admin = Utilisateur::create([
            'nom' => 'Ndiaye',
            'prenom' => 'Omar',
            'email' => 'admin@teranga.sn',
            'telephone' => '77 123 45 67',
            'mot_de_passe' => 'password',
            'role' => Utilisateur::ROLE_ADMIN,
            'statut' => 'actif',
        ]);

        $membres = collect([
            ['nom' => 'Diop', 'prenom' => 'Rokhaya', 'email' => 'rokhaya@teranga.sn', 'telephone' => '77 234 56 78'],
            ['nom' => 'Keïta', 'prenom' => 'Fatou', 'email' => 'fatou@teranga.sn', 'telephone' => '77 345 67 89'],
            ['nom' => 'Sow', 'prenom' => 'Moussa', 'email' => 'moussa@teranga.sn', 'telephone' => '77 456 78 90'],
            ['nom' => 'Ba', 'prenom' => 'Aïssatou', 'email' => 'aissatou@teranga.sn', 'telephone' => '77 567 89 01'],
        ])->map(fn ($m) => Utilisateur::create($m + [
            'mot_de_passe' => 'password',
            'role' => Utilisateur::ROLE_MEMBRE,
            'statut' => 'actif',
        ]));

        // ---------------------------------------------------- ressources

        $salles = collect([
            ['nom' => 'Salle de basket', 'description' => 'Terrain aux normes, panneaux réglables.', 'capacite' => 40, 'image' => 'salle-basket.webp'],
            ['nom' => 'Salle de football en salle', 'description' => 'Terrain de futsal avec revêtement synthétique.', 'capacite' => 30, 'image' => 'salle-futsal.webp'],
            ['nom' => 'Court de tennis n°1', 'description' => 'Court extérieur en résine synthétique.', 'capacite' => 4, 'image' => 'court-tennis.webp'],
            ['nom' => 'Salle de musculation', 'description' => 'Équipements de renforcement musculaire.', 'capacite' => 20, 'image' => 'salle-musculation.webp'],
        ])->map(fn ($s) => Ressource::create($s + [
            'type' => Ressource::TYPE_SALLE,
            'disponible' => true,
        ]));

        $equipements = collect([
            ['nom' => 'Lot de ballons de basket', 'description' => 'Dix ballons taille 7.', 'quantite' => 10, 'etat' => 'bon', 'image' => 'ballons-basket.webp'],
            ['nom' => 'Raquettes de tennis', 'description' => 'Huit raquettes adultes.', 'quantite' => 8, 'etat' => 'bon', 'image' => 'raquettes-tennis.webp'],
            ['nom' => 'Filet de volley-ball', 'description' => 'Filet complet avec poteaux.', 'quantite' => 2, 'etat' => 'moyen', 'image' => 'filet-volley.webp'],
            ['nom' => 'Tapis de sol', 'description' => 'Tapis pour échauffement et étirements.', 'quantite' => 15, 'etat' => 'bon', 'image' => 'tapis-sol.webp'],
        ])->map(fn ($e) => Ressource::create($e + [
            'type' => Ressource::TYPE_EQUIPEMENT,
            'disponible' => true,
        ]));

        // --------------------------------------------------- reservations

        $ressources = $salles->concat($equipements)->values();

        /**
         * Enregistre une reservation et date la demande quelques jours avant
         * le creneau (la colonne date_demande vaut « maintenant » par defaut,
         * ce qui n'aurait pas de sens pour un creneau passe).
         */
        $reserver = function ($iMembre, $ressource, $debut, $duree, $statut, $motif = null)
                use ($membres, $admin) {
            $reservation = Reservation::create([
                'id_membre' => $membres[$iMembre % $membres->count()]->id,
                'id_ressource' => $ressource->id,
                'id_administrateur' => in_array($statut, [
                    Reservation::STATUT_VALIDEE, Reservation::STATUT_REFUSEE,
                ], true) ? $admin->id : null,
                'date_debut' => $debut,
                'date_fin' => $debut->copy()->addHours($duree),
                'statut' => $statut,
                'motif' => $motif,
            ]);

            $reservation->date_demande = $debut->copy()->subDays(4)->setTime(9, 30);
            $reservation->save();

            return $reservation;
        };

        // --- historique : les six mois precedents
        //
        // Le tableau de bord affiche l'evolution des reservations sur les six
        // derniers mois. Sans historique, le graphique ne montrerait qu'un
        // seul point. Le nombre de reservations par mois augmente peu a peu,
        // ce qui traduit l'adoption progressive de l'application par le club.
        //
        // [mois en arriere, nombre de reservations ce mois-la]
        $historique = [[6, 3], [5, 4], [4, 6], [3, 7], [2, 9], [1, 11]];

        $jours = [3, 6, 9, 12, 15, 18, 21, 24, 26, 27, 28];
        $heures = [9, 10, 15, 16, 17, 18, 19];
        $durees = [1, 2, 2, 3];
        $n = 0;

        foreach ($historique as [$recul, $combien]) {
            $moisDebut = now()->subMonths($recul)->startOfMonth();

            for ($i = 0; $i < $combien; $i++, $n++) {
                $debut = $moisDebut->copy()
                    ->addDays($jours[$i % count($jours)] - 1)
                    ->setTime($heures[$n % count($heures)], 0);

                // une reservation sur sept a ete refusee, une sur onze annulee
                $statut = Reservation::STATUT_VALIDEE;
                $motif = null;
                if ($n % 7 === 3) {
                    $statut = Reservation::STATUT_REFUSEE;
                    $motif = 'Ressource déjà occupée sur ce créneau.';
                } elseif ($n % 11 === 5) {
                    $statut = Reservation::STATUT_ANNULEE;
                }

                $reserver($n, $ressources[$n % $ressources->count()], $debut,
                          $durees[$n % count($durees)], $statut, $motif);
            }
        }

        // --- mois en cours : creneaux a venir et demandes a traiter
        $creneaux = [
            // [membre, ressource, jours a partir d'aujourd'hui, heure, duree, statut]
            [0, $salles[0], 2, 18, 2, Reservation::STATUT_VALIDEE],
            [1, $salles[1], 3, 17, 2, Reservation::STATUT_VALIDEE],
            [2, $salles[2], 4, 9, 1, Reservation::STATUT_EN_ATTENTE],
            [3, $salles[3], 5, 19, 1, Reservation::STATUT_EN_ATTENTE],
            [0, $equipements[0], 6, 15, 3, Reservation::STATUT_VALIDEE],
            [1, $salles[0], 7, 10, 2, Reservation::STATUT_EN_ATTENTE],
            [2, $equipements[1], 8, 16, 2, Reservation::STATUT_REFUSEE],
            [3, $salles[1], -5, 18, 2, Reservation::STATUT_VALIDEE],   // creneau passe
            [0, $salles[2], -3, 11, 1, Reservation::STATUT_ANNULEE],
        ];

        foreach ($creneaux as [$iMembre, $ressource, $decalage, $heure, $duree, $statut]) {
            $reserver($iMembre, $ressource,
                      now()->addDays($decalage)->setTime($heure, 0), $duree, $statut,
                      $statut === Reservation::STATUT_REFUSEE
                          ? 'Équipement en maintenance sur ce créneau.'
                          : null);
        }

        // -------------------------------------------------- notifications

        Notification::envoyer($membres[0]->id, 'Votre réservation de « Salle de basket » a été validée.');
        Notification::envoyer($membres[2]->id, 'Votre réservation de « Raquettes de tennis » a été refusée : Équipement en maintenance sur ce créneau.');
        Notification::envoyer($admin->id, 'Nouvelle demande de réservation de Moussa Sow pour « Court de tennis n°1 ».');

        $this->command->info('Jeu de données créé :');
        $this->command->info('  administrateur : admin@teranga.sn / password');
        $this->command->info('  membre         : fatou@teranga.sn / password');
    }
}
