<?php
namespace App\Filament\Admin\Resources\Produits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProduitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')
                    ->label('Produit')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unite')
                    ->label('Unite')
                    ->badge(),

                TextColumn::make('saison')
                    ->label('Saison')
                    ->placeholder('Non precisee'),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color(fn ($record) =>
                        $record->stock <= $record->seuil_alerte
                            ? 'danger'
                            : 'success'
                    ),

                TextColumn::make('seuil_alerte')
                    ->label('Seuil alerte')
                    ->numeric(decimalPlaces: 2),

                IconColumn::make('actif')
                    ->label('Disponible')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Derniere modification')
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
                    DeleteBulkAction::make()
                        ->label('Supprimer la selection'),
                ]),
            ]);
    }
}