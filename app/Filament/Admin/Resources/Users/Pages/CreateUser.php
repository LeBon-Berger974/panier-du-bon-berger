<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Role;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $roleIds = $data['role_ids'] ?? [];
            $permissions = $data['permissions'] ?? [];

            unset($data['role_ids'], $data['permissions']);

            // Le rôle préparateur ne peut pas être attribué manuellement.
            $preparateurRoleId = Role::where('name', 'preparateur')->value('id');

            $roleIds = array_values(array_filter(
                $roleIds,
                fn ($id): bool => (int) $id !== (int) $preparateurRoleId,
            ));

            $user = User::create($data);

            $user->syncRoles($roleIds);
            $user->syncPermissions($permissions);

            return $user;
        });
    }
}
