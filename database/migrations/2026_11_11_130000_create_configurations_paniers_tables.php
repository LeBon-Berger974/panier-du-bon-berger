<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configurations_paniers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_livraison_id')->constrained('sessions_livraison')->cascadeOnDelete();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('photo')->nullable();
            $table->decimal('prix_ht', 10, 2);
            $table->boolean('publie')->default(false);
            $table->timestamps();
            $table->index(['session_livraison_id', 'publie']);
        });

        Schema::create('configuration_panier_produits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('configuration_panier_id')->constrained('configurations_paniers')->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->decimal('quantite', 10, 3);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['configuration_panier_id', 'produit_id']);
        });

        Schema::table('commandes', function (Blueprint $table): void {
            $table->foreignId('configuration_panier_id')
                ->nullable()
                ->constrained('configurations_paniers')
                ->nullOnDelete();
            $table->string('configuration_panier_nom')->nullable();
        });

        // Preserve already configured delivery baskets in the new catalogue model.
        $reglages = DB::table('reglages_site')->first();
        $prixHt = (float) ($reglages->prix_panier_ht ?? config('panier.prix_panier_ht_euros', 20));
        foreach (DB::table('sessions_livraison')->orderBy('id')->get() as $session) {
            $composition = DB::table('composition_paniers')->where('session_livraison_id', $session->id)->get();
            if ($composition->isEmpty()) {
                continue;
            }

            $configurationId = DB::table('configurations_paniers')->insertGetId([
                'session_livraison_id' => $session->id,
                'nom' => 'Panier de la semaine',
                'prix_ht' => $prixHt,
                'publie' => $session->statut === 'ouverte',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($composition as $ligne) {
                DB::table('configuration_panier_produits')->insert([
                    'configuration_panier_id' => $configurationId,
                    'produit_id' => $ligne->produit_id,
                    'quantite' => $ligne->quantite,
                    'actif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('configuration_panier_id');
            $table->dropColumn('configuration_panier_nom');
        });

        Schema::dropIfExists('configuration_panier_produits');
        Schema::dropIfExists('configurations_paniers');
    }
};
