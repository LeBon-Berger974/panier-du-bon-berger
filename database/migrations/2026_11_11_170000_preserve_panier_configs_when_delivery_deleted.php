<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configurations_paniers', function (Blueprint $table): void {
            $table->dropForeign(['session_livraison_id']);
            $table->foreign('session_livraison_id')
                ->references('id')
                ->on('sessions_livraison')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('configurations_paniers', function (Blueprint $table): void {
            $table->dropForeign(['session_livraison_id']);
            $table->foreign('session_livraison_id')
                ->references('id')
                ->on('sessions_livraison')
                ->cascadeOnDelete();
        });
    }
};
