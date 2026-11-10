<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role_ids'] = $this->record->roles()
            ->where('roles.name', '!=', 'preparateur')
            ->pluck('roles.id')
            ->all();

        $data['permissions'] = $this->record->permissions ?? [];

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            /** @var User $record */

            $roleIds = $data['role_ids'] ?? [];
            $permissions = $data['permissions'] ?? [];

            unset($data['role_ids'], $data['permissions']);

            $wasPreparateur = $record->hasRole('preparateur');

            // Le rôle préparateur est géré automatiquement.
            $preparateurRoleId = Role::where('name', 'preparateur')->value('id');

            $roleIds = array_values(array_filter(
                $roleIds,
                fn ($id): bool => (int) $id !== (int) $preparateurRoleId,
            ));

            // Si un préparateur perd toutes ses permissions,
            // il conserve ou récupère le rôle client.
            if ($wasPreparateur && empty($permissions)) {
                $clientRoleId = Role::where('name', 'client')->firstOrFail()->id;

                if (! in_array((int) $clientRoleId, array_map('intval', $roleIds), true)) {
                    $roleIds[] = $clientRoleId;
                }
            }

            $record->update($data);

            $record->syncRoles($roleIds);
            $record->syncPermissions($permissions);

            return $record;
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Consulter'),
        ];
    }
}
