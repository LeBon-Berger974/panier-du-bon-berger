<?php

namespace App\Filament\Admin\Resources\SessionsLivraison\Tables;

use App\Models\Commande;
use App\Models\DisponibiliteConfigurationPanier;
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

                TextColumn::make('configurations_faisables')
                    ->label('Configurations · paniers faisables')
                    ->state(function (SessionLivraison $record): string {
                        $configurations = $record->configurations()->with(['produits.produit'])->get();
                        if ($configurations->isEmpty()) {
                            return 'Aucune configuration';
                        }

                        $disponibilites = DisponibiliteConfigurationPanier::pour($configurations, $record);

                        return $configurations->map(fn ($configuration): string => $configuration->nom
                            .' · '.number_format((float) $configuration->prix_ht, 2, ',', ' ').' € HT'
                            .' · '.($disponibilites[$configuration->id]['faisable'] ?? 0)
                            .' faisable(s) · '.$configuration->statutPublication())
                            ->join("\n");
                    })
                    ->listWithLineBreaks()
                    ->wrap(),

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
