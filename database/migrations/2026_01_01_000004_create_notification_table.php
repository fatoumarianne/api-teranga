<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table notification : informe un utilisateur d'un evenement le concernant
 * (nouvelle demande pour l'administrateur, decision pour le membre).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_utilisateur')
                ->constrained('utilisateur')
                ->cascadeOnDelete();

            $table->text('message');
            $table->timestamp('date_envoi')->useCurrent();
            $table->boolean('lu')->default(false);

            $table->timestamps();

            $table->index(['id_utilisateur', 'lu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification');
    }
};
