<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panier_reservations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('panier_id')
                ->constrained('paniers')
                ->cascadeOnDelete();

            $table->unsignedInteger('nombre');
            $table->unsignedInteger('nombre_prepare')->default(0);
            
            // reserve = réservation en attente de préparation
            // prepare = réservation préparée
            // annule = réservation annulée
            $table->string('statut')->default('reserve');

            // Conserve la composition au moment de la réservation.
            $table->json('composition');

            $table->timestamp('annule_at')->nullable();

            $table->timestamps();

            $table->index(['panier_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panier_reservations');
    }
};
