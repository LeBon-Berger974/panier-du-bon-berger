<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configurations_paniers', function (Blueprint $table): void {
            $table->foreignId('session_livraison_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Configurations créées sans livraison doivent être associées avant le rollback.
        Schema::table('configurations_paniers', function (Blueprint $table): void {
            $table->foreignId('session_livraison_id')->nullable(false)->change();
        });
    }
};
