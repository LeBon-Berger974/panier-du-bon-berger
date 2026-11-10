<?php

namespace App\Filament\Admin\Resources\Produits\Tables;

use App\Models\Produit;
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
                    ->label('Produit')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('saison')
                    ->label('Saison')
                    ->placeholder('Non précisée'),

                TextColumn::make('unite')
                    ->label('Unité')
                    ->badge(),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('seuil_alerte')
                    ->label("Seuil d'alerte")
                    ->numeric(decimalPlaces: 2),

                TextColumn::make('etat_stock')
                    ->label('ETAT')
                    ->state(fn($record): string => match (true) {
                        $record->stock <= 0 => 'Épuisé',
                        $record->stock <= $record->seuil_alerte => 'Stock bas',
                        default => 'En stock',
                    })
                    ->badge()
                    ->color(fn($record): string => match (true) {
                        $record->stock <= 0 => 'danger',
                        $record->stock <= $record->seuil_alerte => 'warning',
                        default => 'success',
                    }),

                ToggleColumn::make('actif')
                    ->label('Réservable')
                    ->onColor('success')
                    ->offColor('danger')
                    ->sortable()
                    ->visible(fn(): bool => auth()->user()?->canUsePermission('products.toggle') ?? false)
                    ->beforeStateUpdated(function (): void {
                        abort_unless(
                            auth()->user()?->canUsePermission('products.toggle'),
                            403
                        );
                    }),

                TextColumn::make('updated_at')
                    ->label('Dernière modification')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Modifier')
                    ->visible(
                        fn(): bool =>
                        auth()->user()?->canUsePermission('products.manage')
                            || auth()->user()?->canUsePermission('stock.update')
                            || auth()->user()?->canUsePermission('products.toggle')
                    ),
            ]);
    }
}
