<?php

namespace App\Console\Commands;

use App\Models\Utilisateur;
use Illuminate\Console\Command;

/**
 * Applique au compte administrateur le mot de passe defini dans la variable
 * d'environnement ADMIN_PASSWORD.
 *
 * Le jeu de donnees de demonstration cree le compte avec un mot de passe
 * connu, publie dans le depot. En production, cette commande le remplace au
 * demarrage par une valeur qui n'existe que dans la configuration de
 * l'hebergeur, et qui n'apparait donc jamais dans le code source.
 *
 * Si la variable n'est pas definie, la commande ne fait rien : le
 * developpement en local continue de fonctionner avec le mot de passe par
 * defaut.
 */
class MotDePasseAdmin extends Command
{
    protected $signature = 'teranga:mot-de-passe-admin';

    protected $description = "Applique le mot de passe administrateur defini dans ADMIN_PASSWORD";

    public function handle(): int
    {
        $motDePasse = env('ADMIN_PASSWORD');

        if (blank($motDePasse)) {
            $this->info('ADMIN_PASSWORD non definie : mot de passe inchange.');

            return self::SUCCESS;
        }

        $admin = Utilisateur::where('role', Utilisateur::ROLE_ADMIN)->first();

        if (! $admin) {
            $this->warn('Aucun compte administrateur trouve.');

            return self::SUCCESS;
        }

        // Le modele applique automatiquement le hachage (cast « hashed ») :
        // le mot de passe en clair n'est jamais enregistre.
        $admin->mot_de_passe = $motDePasse;
        $admin->save();

        $this->info("Mot de passe du compte {$admin->email} mis a jour.");

        return self::SUCCESS;
    }
}
