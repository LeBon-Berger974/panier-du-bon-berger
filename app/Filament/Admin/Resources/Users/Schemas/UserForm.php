<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

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

            CheckboxList::make('permissions')
                ->label('Autorisations du préparateur')
                ->options(User::preparateurPermissions())
                ->columns(2)
                ->bulkToggleable()
                ->helperText('Coche uniquement les fonctionnalités que ce compte est autorisé à utiliser. Sans permission, le compte ne peut pas accéder au panneau préparateur.'),
        ]);
    }
}
