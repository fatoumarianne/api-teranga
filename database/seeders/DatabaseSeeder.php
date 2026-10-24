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
            ['nom' => 'Salle de basket', 'description' => 'Terrain couvert aux normes, panneaux réglables.', 'capacite' => 40],
            ['nom' => 'Salle de football en salle', 'description' => 'Terrain de futsal avec revêtement synthétique.', 'capacite' => 30],
            ['nom' => 'Court de tennis n°1', 'description' => 'Court extérieur en terre battue.', 'capacite' => 4],
            ['nom' => 'Salle de musculation', 'description' => 'Équipements de renforcement musculaire.', 'capacite' => 20],
        ])->map(fn ($s) => Ressource::create($s + [
            'type' => Ressource::TYPE_SALLE,
            'disponible' => true,
        ]));

        $equipements = collect([
            ['nom' => 'Lot de ballons de basket', 'description' => 'Dix ballons taille 7.', 'quantite' => 10, 'etat' => 'bon'],
            ['nom' => 'Raquettes de tennis', 'description' => 'Huit raquettes adultes.', 'quantite' => 8, 'etat' => 'bon'],
            ['nom' => 'Filet de volley-ball', 'description' => 'Filet complet avec poteaux.', 'quantite' => 2, 'etat' => 'moyen'],
            ['nom' => 'Tapis de sol', 'description' => 'Tapis pour échauffement et étirements.', 'quantite' => 15, 'etat' => 'bon'],
        ])->map(fn ($e) => Ressource::create($e + [
            'type' => Ressource::TYPE_EQUIPEMENT,
            'disponible' => true,
        ]));

        // --------------------------------------------------- reservations

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

        foreach ($creneaux as [$iMembre, $ressource, $jours, $heure, $duree, $statut]) {
            $debut = now()->addDays($jours)->setTime($heure, 0);

            Reservation::create([
                'id_membre' => $membres[$iMembre]->id,
                'id_ressource' => $ressource->id,
                'id_administrateur' => in_array($statut, [
                    Reservation::STATUT_VALIDEE, Reservation::STATUT_REFUSEE,
                ], true) ? $admin->id : null,
                'date_debut' => $debut,
                'date_fin' => $debut->copy()->addHours($duree),
                'statut' => $statut,
                'motif' => $statut === Reservation::STATUT_REFUSEE
                    ? 'Équipement en maintenance sur ce créneau.'
                    : null,
            ]);
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
