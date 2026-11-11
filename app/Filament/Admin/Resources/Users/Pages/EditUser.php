<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->editedUser();
        $data['role_ids'] = $record->roles()
            ->where('roles.name', '!=', 'preparateur')
            ->pluck('roles.id')
            ->all();

        $data['permissions'] = $record->permissions ?? [];
        $data['point_relais_ids'] = $record->pointsRelaisAffectes()->pluck('points_relais.id')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof User) {
            throw new LogicException('Le compte à modifier est indisponible.');
        }

        return DB::transaction(function () use ($record, $data) {
            $roleIds = $data['role_ids'] ?? [];
            $permissions = $data['permissions'] ?? [];
            $pointRelaisIds = $data['point_relais_ids'] ?? [];

            unset($data['role_ids'], $data['permissions'], $data['point_relais_ids']);

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
            if ($record->hasRole('responsable_relais') && empty($pointRelaisIds)) {
                throw ValidationException::withMessages(['point_relais_ids' => 'Attribuez au moins un point relais à ce responsable.']);
            }
            $record->pointsRelaisAffectes()->sync($pointRelaisIds);

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

    private function editedUser(): User
    {
        $record = $this->getRecord();

        if (! $record instanceof User) {
            throw new LogicException('Le compte à modifier est indisponible.');
        }

        return $record;
    }
}
