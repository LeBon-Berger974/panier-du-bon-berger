<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creneaux_retrait', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('point_relais_id')->constrained('points_relais')->cascadeOnDelete();
            $table->string('jour', 12);
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->timestamps();
            $table->unique(['point_relais_id', 'jour']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creneaux_retrait');
    }
};
