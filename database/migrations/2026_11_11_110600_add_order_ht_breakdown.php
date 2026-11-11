<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table): void {
            $table->decimal('montant_panier_ht', 10, 2)->default(0);
            $table->decimal('montant_consigne_ht', 10, 2)->default(0);
            $table->decimal('montant_tva', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table): void {
            $table->dropColumn(['montant_panier_ht', 'montant_consigne_ht', 'montant_tva']);
        });
    }
};
