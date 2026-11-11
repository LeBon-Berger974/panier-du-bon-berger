<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table): void {
            $table->string('unite')->nullable()->default(null)->change();
            $table->decimal('seuil_alerte', 10, 2)->default(5)->change();
        });

        DB::table('produits')->where('seuil_alerte', 0)->update(['seuil_alerte' => 5]);
    }

    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table): void {
            $table->string('unite')->default('piece')->change();
            $table->decimal('seuil_alerte', 10, 2)->default(0)->change();
        });
    }
};
