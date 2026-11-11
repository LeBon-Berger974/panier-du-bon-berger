<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_relais', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('adresse');
            $table->string('complement')->nullable();
            $table->string('code_postal', 12);
            $table->string('ville');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('contact_nom')->nullable();
            $table->string('contact_telephone', 30)->nullable();
            $table->unsignedInteger('minimum_paniers')->default(5);
            $table->boolean('retrait_ferme')->default(false);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->index(['actif', 'code_postal']);
        });

        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 24)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('session_livraison_id')->constrained('sessions_livraison')->restrictOnDelete();
            $table->foreignId('point_relais_id')->nullable()->constrained('points_relais')->restrictOnDelete();
            $table->string('mode_retrait', 16);
            $table->unsignedInteger('nombre_paniers');
            $table->decimal('prix_unitaire', 10, 2);
            $table->decimal('montant_paniers', 10, 2);
            $table->decimal('montant_consigne', 10, 2)->default(0);
            $table->decimal('montant_total', 10, 2);
            $table->string('statut', 24)->default('a_payer');
            $table->json('composition')->nullable();
            $table->timestamp('payee_at')->nullable();
            $table->timestamp('preparee_at')->nullable();
            $table->timestamp('remise_relais_at')->nullable();
            $table->timestamp('retiree_at')->nullable();
            $table->timestamp('annulee_at')->nullable();
            $table->timestamps();
            $table->index(['session_livraison_id', 'statut']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->string('prestataire', 24)->default('stripe');
            $table->string('reference_prestataire')->nullable()->unique();
            $table->string('statut', 24)->default('en_attente');
            $table->unsignedInteger('montant_centimes');
            $table->string('devise', 3)->default('eur');
            $table->timestamp('confirme_at')->nullable();
            $table->json('donnees')->nullable();
            $table->timestamps();
            $table->index(['prestataire', 'statut']);
        });

        Schema::create('mouvements_consignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->string('type', 20);
            $table->unsignedInteger('quantite');
            $table->decimal('montant_unitaire', 10, 2)->default(6);
            $table->timestamp('effectue_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['commande_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_consignes');
        Schema::dropIfExists('paiements');
        Schema::dropIfExists('commandes');
        Schema::dropIfExists('points_relais');
    }
};
