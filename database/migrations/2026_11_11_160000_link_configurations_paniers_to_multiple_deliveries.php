<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuration_panier_session_livraison', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('configuration_panier_id')->constrained('configurations_paniers')->cascadeOnDelete();
            $table->foreignId('session_livraison_id')->constrained('sessions_livraison')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['configuration_panier_id', 'session_livraison_id'], 'config_panier_session_unique');
        });

        DB::table('configurations_paniers')
            ->whereNotNull('session_livraison_id')
            ->orderBy('id')
            ->chunkById(500, function ($configurations): void {
                $now = now();
                DB::table('configuration_panier_session_livraison')->insertOrIgnore(
                    $configurations->map(fn ($configuration): array => [
                        'configuration_panier_id' => $configuration->id,
                        'session_livraison_id' => $configuration->session_livraison_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all(),
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuration_panier_session_livraison');
    }
};
