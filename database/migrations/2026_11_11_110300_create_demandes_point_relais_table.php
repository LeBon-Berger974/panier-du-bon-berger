<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_point_relais', function (Blueprint $table): void {
            $table->id();
            $table->string('nom_lieu');
            $table->string('contact');
            $table->string('commune');
            $table->string('jour_souhaite', 16);
            $table->unsignedInteger('nombre_paniers_estime');
            $table->string('statut', 20)->default('a_traiter');
            $table->timestamps();
            $table->index(['statut', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_point_relais');
    }
};
