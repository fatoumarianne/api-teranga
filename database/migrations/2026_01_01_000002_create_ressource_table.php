<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table ressource : les salles et les equipements du club.
 *
 * Comme pour utilisateur, l'heritage Salle / Equipement est traduit par une
 * table unique avec une colonne « type ». Les colonnes capacite (propre aux
 * salles) et quantite / etat (propres aux equipements) sont donc nullables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ressource', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['salle', 'equipement']);
            $table->string('nom');
            $table->text('description')->nullable();
            $table->boolean('disponible')->default(true);

            // specifique aux salles
            $table->unsignedInteger('capacite')->nullable();

            // specifiques aux equipements
            $table->unsignedInteger('quantite')->nullable();
            $table->string('etat')->nullable();

            $table->timestamps();

            $table->index('type');
            $table->index('disponible');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ressource');
    }
};
