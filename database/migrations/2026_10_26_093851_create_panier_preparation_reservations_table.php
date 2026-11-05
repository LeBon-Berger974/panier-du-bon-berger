<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panier_preparation_reservations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('panier_preparation_id')
                ->constrained('panier_preparations')
                ->cascadeOnDelete();

            $table->foreignId('panier_reservation_id')
                ->constrained('panier_reservations')
                ->restrictOnDelete();

            $table->unsignedInteger('nombre');

            $table->timestamps();

            $table->unique([
                'panier_preparation_id',
                'panier_reservation_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panier_preparation_reservations');
    }
};
