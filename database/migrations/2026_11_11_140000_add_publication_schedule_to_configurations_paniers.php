<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configurations_paniers', function (Blueprint $table): void {
            $table->string('publication_mode', 16)->default('manuel')->after('publie');
            $table->date('publication_du')->nullable()->after('publication_mode');
            $table->date('publication_au')->nullable()->after('publication_du');
        });
    }

    public function down(): void
    {
        Schema::table('configurations_paniers', function (Blueprint $table): void {
            $table->dropColumn(['publication_mode', 'publication_du', 'publication_au']);
        });
    }
};
