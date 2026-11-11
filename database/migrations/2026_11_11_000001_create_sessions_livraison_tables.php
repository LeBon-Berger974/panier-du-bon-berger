<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions_livraison', function (Blueprint $table) {
            $table->id();
            $table->date('date_livraison')->unique();
            $table->string('jour', 12);
            $table->dateTime('ouverture_reservations_at');
            $table->dateTime('cloture_reservations_at');
            $table->unsignedInteger('capacite_max');
            $table->string('statut', 20)->default('brouillon');
            $table->timestamps();

            $table->index(['statut', 'ouverture_reservations_at']);
            $table->index(['statut', 'cloture_reservations_at']);
        });

        Schema::create('composition_paniers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_livraison_id')
                ->constrained('sessions_livraison')
                ->cascadeOnDelete();
            $table->foreignId('produit_id')
                ->constrained('produits')
                ->restrictOnDelete();
            $table->decimal('quantite', 10, 3);
            $table->timestamps();

            $table->unique(['session_livraison_id', 'produit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composition_paniers');
        Schema::dropIfExists('sessions_livraison');
    }
};
