<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reglages_site', function (Blueprint $table): void {
            $table->time('ouverture_reservations_mercredi')->nullable();
            $table->time('cloture_reservations_mercredi')->nullable();
            $table->time('ouverture_reservations_vendredi')->nullable();
            $table->time('cloture_reservations_vendredi')->nullable();
            $table->string('email_contact')->nullable();
            $table->text('texte_accueil')->nullable();
            $table->string('sujet_email_confirmation')->nullable();
            $table->text('texte_email_confirmation')->nullable();
            $table->string('sujet_email_rappel')->nullable();
            $table->text('texte_email_rappel')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reglages_site', function (Blueprint $table): void {
            $table->dropColumn([
                'ouverture_reservations_mercredi', 'cloture_reservations_mercredi',
                'ouverture_reservations_vendredi', 'cloture_reservations_vendredi',
                'email_contact', 'texte_accueil', 'sujet_email_confirmation', 'texte_email_confirmation',
                'sujet_email_rappel', 'texte_email_rappel',
            ]);
        });
    }
};
