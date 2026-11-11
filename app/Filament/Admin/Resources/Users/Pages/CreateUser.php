<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Role;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $roleIds = $data['role_ids'] ?? [];
            $permissions = $data['permissions'] ?? [];
            $pointRelaisIds = $data['point_relais_ids'] ?? [];

            unset($data['role_ids'], $data['permissions'], $data['point_relais_ids']);

            // Le rôle préparateur ne peut pas être attribué manuellement.
            $preparateurRoleId = Role::where('name', 'preparateur')->value('id');

            $roleIds = array_values(array_filter(
                $roleIds,
                fn ($id): bool => (int) $id !== (int) $preparateurRoleId,
            ));

            $user = User::create($data);

            $user->syncRoles($roleIds);
            $user->syncPermissions($permissions);
            if ($user->hasRole('responsable_relais') && empty($pointRelaisIds)) {
                throw ValidationException::withMessages(['point_relais_ids' => 'Attribuez au moins un point relais à ce responsable.']);
            }
            $user->pointsRelaisAffectes()->sync($pointRelaisIds);

            return $user;
        });
    }
}
