<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.label')
                    ->label('Rôles')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Inscription')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make()
                    ->label('Consulter'),

                EditAction::make()
                    ->label('Modifier'),
            ])
            ->toolbarActions([]);
    }
}