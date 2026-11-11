<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roles = [
            'admin' => 'Administrateur',
            'client' => 'Client',
            'preparateur' => 'Préparateur',
        ];

        foreach ($roles as $name => $label) {
            DB::table('roles')->updateOrInsert(
                ['name' => $name],
                [
                    'label' => $label,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $users = DB::table('users')->select('id', 'role')->get();

        foreach ($users as $user) {
            $roleId = DB::table('roles')
                ->where('name', $user->role)
                ->value('id');

            if ($roleId) {
                DB::table('role_user')->insertOrIgnore([
                    'user_id' => $user->id,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('role_user')->delete();

        DB::table('roles')
            ->whereIn('name', ['admin', 'client', 'preparateur'])
            ->delete();
    }
};
