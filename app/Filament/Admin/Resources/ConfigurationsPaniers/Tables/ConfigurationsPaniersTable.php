<?php

namespace App\Filament\Admin\Resources\ConfigurationsPaniers\Tables;

use App\Models\ConfigurationPanier;
use App\Models\DisponibiliteConfigurationPanier;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConfigurationsPaniersTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            ImageColumn::make('photo')->label('Photo')->disk('public')->square(),
            TextColumn::make('nom')->label('Nom du panier')->searchable()->sortable(),
            TextColumn::make('livraison')
                ->label('Livraisons associées')
                ->state(fn (ConfigurationPanier $record): string => $record->sessions->isEmpty()
                    ? 'À associer dans Livraisons'
                    : $record->sessions->sortBy('date_livraison')->map(fn ($session): string => ucfirst($session->jour).' · '.$session->date_livraison->format('d/m/Y'))->join(', ')),
            TextColumn::make('prix_ht')->label('Prix HT')->money('EUR')->sortable(),
            TextColumn::make('faisable')->label('Paniers faisables')
                ->state(fn (ConfigurationPanier $record): int => DisponibiliteConfigurationPanier::pour(collect([$record]))[$record->id]['faisable'] ?? 0)
                ->badge()->color(fn (int $state): string => $state > 0 ? 'success' : 'warning'),
            TextColumn::make('publication')->label('Visibilité sur le site')
                ->state(fn (ConfigurationPanier $record): string => $record->statutPublication())
                ->badge()
                ->color(fn (ConfigurationPanier $record): string => $record->estVisibleSurSite() ? 'success' : 'gray'),
        ])->defaultSort('created_at', 'desc')->recordActions([
            EditAction::make()->label('Modifier'),
        ]);
    }
}
