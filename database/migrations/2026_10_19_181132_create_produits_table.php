<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
          $table->id();
          $table->string('nom');
          $table->string('photo')->nullable();
          $table->enum('unite', ['kg', 'botte', 'piece'])->default('piece');
          $table->string('saison')->nullable();
          $table->decimal('stock', 10, 2)->default(0);
          $table->decimal('seuil_alerte', 10, 2)->default(0);
          $table->boolean('actif')->default(true);
          $table->timestamps();
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
