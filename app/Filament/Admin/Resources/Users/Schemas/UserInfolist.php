<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Nom complet'),

                TextEntry::make('email')
                    ->label('Adresse e-mail'),

                TextEntry::make('roles.label')
                    ->label('Rôles')
                    ->badge()
                    ->placeholder('Aucun rôle'),

                TextEntry::make('created_at')
                    ->label('Compte créé le')
                    ->dateTime('d/m/Y à H:i'),
            ]);
    }
}