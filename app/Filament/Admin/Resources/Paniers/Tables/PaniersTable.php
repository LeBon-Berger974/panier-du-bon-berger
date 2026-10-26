<?php

namespace App\Filament\Admin\Resources\Produits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ProduitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')
                    ->label('Nom du produit')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unite')
                    ->label('Unité')
                    ->badge()
                    ->sortable(),

                TextColumn::make('saison')
                    ->label('Saison')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('seuil_alerte')
                    ->label("Seuil d'alerte")
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                ToggleColumn::make('actif')
                    ->label('Actif')
                    ->onColor('success')
                    ->offColor('danger')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Dernière modification')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Modifier'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
