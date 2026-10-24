<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table utilisateur.
 *
 * Les noms de table et de colonnes suivent volontairement le modele decrit
 * dans le memoire (francais, au singulier) plutot que les conventions par
 * defaut de Laravel, afin que le code corresponde au diagramme de classes.
 *
 * L'heritage Membre / Administrateur est traduit par une table unique avec
 * une colonne « role » qui distingue les deux cas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateur', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->string('telephone')->nullable();
            $table->string('mot_de_passe');
            $table->enum('role', ['membre', 'administrateur'])->default('membre');
            $table->timestamp('date_inscription')->useCurrent();
            $table->enum('statut', ['actif', 'inactif'])->default('actif');
            $table->rememberToken();
            $table->timestamps();

            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilisateur');
    }
};
