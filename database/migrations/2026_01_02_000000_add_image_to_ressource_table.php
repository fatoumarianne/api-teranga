<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute une illustration aux salles et equipements.
 *
 * La colonne contient soit l'adresse web d'une photo, soit le nom d'une des
 * illustrations fournies avec l'application. Elle peut rester vide : une
 * image par defaut est alors affichee selon le type de ressource.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ressource', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
        });

        // Les ressources creees avant cette migration n'ont pas d'image :
        // on rattache les illustrations fournies a celles du jeu de donnees
        // de demonstration, pour que la page d'accueil soit illustree sans
        // intervention manuelle.
        $illustrations = [
            'Salle de basket' => 'salle-basket.svg',
            'Salle de football en salle' => 'salle-futsal.svg',
            'Court de tennis n°1' => 'court-tennis.svg',
            'Salle de musculation' => 'salle-musculation.svg',
            'Lot de ballons de basket' => 'ballons-basket.svg',
            'Raquettes de tennis' => 'raquettes-tennis.svg',
            'Filet de volley-ball' => 'filet-volley.svg',
            'Tapis de sol' => 'tapis-sol.svg',
        ];

        foreach ($illustrations as $nom => $fichier) {
            DB::table('ressource')
                ->where('nom', $nom)
                ->whereNull('image')
                ->update(['image' => $fichier]);
        }
    }

    public function down(): void
    {
        Schema::table('ressource', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
