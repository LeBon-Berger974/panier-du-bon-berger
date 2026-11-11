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
            $table->boolean('desactive_stock')->default(false);
        });

        DB::table('produits')->where('stock', '<=', 0)->where('actif', false)->update(['desactive_stock' => true]);
    }

    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table): void {
            $table->dropColumn('desactive_stock');
        });
    }
};
