<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->updateOrInsert(
            ['name' => 'responsable_relais'],
            ['label' => 'Responsable de point relais', 'created_at' => now(), 'updated_at' => now()],
        );

        Schema::create('point_relais_user', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('point_relais_id')->constrained('points_relais')->cascadeOnDelete();
            $table->primary(['user_id', 'point_relais_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_relais_user');
        DB::table('roles')->where('name', 'responsable_relais')->delete();
    }
};
