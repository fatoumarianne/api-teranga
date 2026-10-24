<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Table reservation : le coeur de l'application.
 *
 * Une reservation lie un membre a une ressource sur un creneau. Elle est
 * creee « en_attente » puis traitee par un administrateur, qui la valide ou
 * la refuse. Le membre peut l'annuler tant que le creneau n'est pas passe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_membre')
                ->constrained('utilisateur')
                ->cascadeOnDelete();

            $table->foreignId('id_ressource')
                ->constrained('ressource')
                ->restrictOnDelete();   // on ne supprime pas une ressource reservee

            $table->foreignId('id_administrateur')
                ->nullable()
                ->constrained('utilisateur')
                ->nullOnDelete();

            $table->timestamp('date_demande')->useCurrent();
            $table->timestamp('date_debut');
            $table->timestamp('date_fin');

            $table->enum('statut', ['en_attente', 'validee', 'refusee', 'annulee'])
                ->default('en_attente');
            $table->text('motif')->nullable();

            $table->timestamps();

            // la recherche de conflits filtre sur la ressource, le statut et le creneau
            $table->index(['id_ressource', 'statut']);
            $table->index(['date_debut', 'date_fin']);
            $table->index('id_membre');
        });

        // la fin d'un creneau doit suivre son debut (regle du memoire)
        DB::statement(
            'ALTER TABLE reservation ADD CONSTRAINT reservation_creneau_valide '
            . 'CHECK (date_fin > date_debut)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation');
    }
};
