<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remplace les illustrations dessinees par des photographies.
 *
 * Les ressources de demonstration pointaient vers des dessins au format
 * SVG. Cette migration fait pointer chacune vers la photographie
 * correspondante, au format WebP, plus leger qu'un JPEG a qualite egale.
 *
 * Seules les ressources portant encore l'ancien nom de fichier sont
 * touchees : une image choisie a la main depuis l'ecran d'administration
 * n'est jamais ecrasee.
 */
return new class extends Migration
{
    /** ancien fichier => nouveau fichier */
    private array $photos = [
        'salle-basket.svg' => 'salle-basket.webp',
        'salle-futsal.svg' => 'salle-futsal.webp',
        'court-tennis.svg' => 'court-tennis.webp',
        'salle-musculation.svg' => 'salle-musculation.webp',
        'ballons-basket.svg' => 'ballons-basket.webp',
        'raquettes-tennis.svg' => 'raquettes-tennis.webp',
        'filet-volley.svg' => 'filet-volley.webp',
        'tapis-sol.svg' => 'tapis-sol.webp',
    ];

    /**
     * Deux descriptions ne correspondaient plus a la photographie retenue :
     * le terrain de basket est en plein air, et le court de tennis a un
     * revetement synthetique et non de la terre battue. On ne remplace le
     * texte que s'il est reste celui d'origine.
     */
    private array $descriptions = [
        'Salle de basket' => [
            'Terrain couvert aux normes, panneaux réglables.',
            'Terrain aux normes, panneaux réglables.',
        ],
        'Court de tennis n°1' => [
            'Court extérieur en terre battue.',
            'Court extérieur en résine synthétique.',
        ],
    ];

    public function up(): void
    {
        foreach ($this->photos as $ancien => $nouveau) {
            DB::table('ressource')
                ->where('image', $ancien)
                ->update(['image' => $nouveau]);
        }

        foreach ($this->descriptions as $nom => [$ancienne, $nouvelle]) {
            DB::table('ressource')
                ->where('nom', $nom)
                ->where('description', $ancienne)
                ->update(['description' => $nouvelle]);
        }
    }

    public function down(): void
    {
        foreach ($this->photos as $ancien => $nouveau) {
            DB::table('ressource')
                ->where('image', $nouveau)
                ->update(['image' => $ancien]);
        }
    }
};
