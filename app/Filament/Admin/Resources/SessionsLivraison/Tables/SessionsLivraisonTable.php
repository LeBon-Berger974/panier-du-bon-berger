<?php

namespace App\Filament\Admin\Resources\SessionsLivraison\Tables;

use App\Models\Commande;
use App\Models\SessionLivraison;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SessionsLivraisonTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date_livraison')
                    ->label('Livraison')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('jour')
                    ->label('Jour')
                    ->badge()
                    ->color('info'),

                TextColumn::make('ouverture_reservations_at')
                    ->label('Ouverture')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('cloture_reservations_at')
                    ->label('Clôture')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('paniers_reserves')
                    ->label('Paniers réservés')
                    ->state(fn (SessionLivraison $record): int => (int) Commande::query()
                        ->where('session_livraison_id', $record->id)
                        ->whereIn('statut', [Commande::A_PAYER, Commande::PAYEE, Commande::PREPAREE, Commande::AU_RELAIS, Commande::RETIREE])
                        ->sum('nombre_paniers')),

                TextColumn::make('paniers_possibles')
                    ->label('Possibles selon stock')
                    ->state(function (SessionLivraison $record): int {
                        $lignes = $record->compositions()->with('produit')->get();
                        if ($lignes->isEmpty()) {
                            return 0;
                        }

                        return (int) $lignes->map(function ($ligne): int {
                            if (! $ligne->produit || (float) $ligne->quantite <= 0) {
                                return 0;
                            }

                            return (int) floor((float) $ligne->produit->stock / (float) $ligne->quantite);
                        })->min();
                    }),

                TextColumn::make('capacite_max')
                    ->label('Capacité')
                    ->suffix(' paniers')
                    ->sortable(),

                TextColumn::make('statut')
                    ->label('État')
                    ->formatStateUsing(fn (SessionLivraison $record): string => $record->statutLabel())
                    ->badge()
                    ->color(fn (SessionLivraison $record): string => match ($record->statut) {
                        SessionLivraison::STATUT_OUVERTE => 'success',
                        SessionLivraison::STATUT_CLOTUREE => 'warning',
                        SessionLivraison::STATUT_LIVREE => 'gray',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('date_livraison')
            ->recordActions([
                Action::make('preparation')
                    ->label('Feuille de préparation')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->url(fn (SessionLivraison $record): string => route('admin.livraisons.preparation', $record))
                    ->openUrlInNewTab(),
                Action::make('tournee')
                    ->label('Tournée')
                    ->icon('heroicon-o-map')
                    ->url(fn (SessionLivraison $record): string => route('admin.livraisons.tournee', $record))
                    ->openUrlInNewTab(),
                EditAction::make()->label('Modifier'),
            ]);
    }
}
