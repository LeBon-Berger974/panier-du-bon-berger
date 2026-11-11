<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table): void {
            $table->timestamp('rappel_envoye_at')->nullable()->after('retiree_at');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table): void {
            $table->dropColumn('rappel_envoye_at');
        });
    }
};
