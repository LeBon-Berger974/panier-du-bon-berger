<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\PointRelais;
use App\Models\Role;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nom complet')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('Adresse e-mail')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('password')
                ->label('Mot de passe')
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->minLength(8),

            Select::make('role_ids')
                ->label('Rôles du compte')
                ->multiple()
                ->options(fn (): array => Role::query()
                    ->where('name', '!=', 'preparateur')
                    ->orderBy('label')
                    ->pluck('label', 'id')
                    ->all())
                ->searchable()
                ->preload()
                ->helperText('Le rôle préparateur est attribué automatiquement selon les permissions.'),

            Select::make('point_relais_favori_id')
                ->label('Point relais favori')
                ->relationship(name: 'pointRelaisFavori', titleAttribute: 'nom')
                ->searchable()
                ->preload()
                ->nullable(),

            Select::make('point_relais_ids')->label('Points relais attribués au responsable')->multiple()
                ->options(fn (): array => PointRelais::query()->orderBy('nom')->pluck('nom', 'id')->all())
                ->searchable()->preload()
                ->helperText('Utilisé uniquement si le rôle Responsable de point relais est attribué.'),

            CheckboxList::make('permissions')
                ->label('Autorisations du préparateur')
                ->options(User::preparateurPermissions())
                ->columns(2)
                ->bulkToggleable()
                ->helperText('Coche uniquement les fonctionnalités préparateur autorisées. Le responsable de point relais accède au panneau uniquement pour ses relais attribués.'),
        ]);
    }
}
