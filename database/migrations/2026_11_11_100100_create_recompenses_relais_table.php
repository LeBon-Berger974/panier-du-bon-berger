<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recompenses_relais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_relais_id')->constrained('points_relais')->restrictOnDelete();
            $table->foreignId('session_livraison_id')->constrained('sessions_livraison')->restrictOnDelete();
            $table->unsignedInteger('numero_palier');
            $table->unsignedInteger('nombre_paniers_offerts')->default(1);
            $table->string('statut', 20)->default('a_remettre');
            $table->timestamp('remis_at')->nullable();
            $table->timestamps();
            $table->unique(['point_relais_id', 'session_livraison_id', 'numero_palier'], 'recompenses_relais_palier_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recompenses_relais');
    }
};
