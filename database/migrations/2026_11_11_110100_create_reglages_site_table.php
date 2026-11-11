<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglages_site', function (Blueprint $table): void {
            $table->id();
            $table->decimal('prix_panier_ht', 8, 2)->default(20);
            $table->decimal('consigne_ht', 8, 2)->default(6);
            $table->decimal('taux_tva', 5, 2)->default(2.1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglages_site');
    }
};
