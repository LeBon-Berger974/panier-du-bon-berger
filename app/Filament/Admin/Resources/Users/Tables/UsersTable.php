<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Models\MouvementConsigne;
use App\Models\User;
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

                TextColumn::make('pointRelaisFavori.nom')
                    ->label('Relais favori')
                    ->placeholder('—'),

                TextColumn::make('contenants_consignes')
                    ->label('Contenants chez le client')
                    ->state(function (User $record): int {
                        $mouvements = MouvementConsigne::query()
                            ->whereHas('commande', fn ($query) => $query->where('user_id', $record->id)->whereNotIn('statut', ['a_payer', 'annulee']))
                            ->get();

                        return max(0,
                            (int) $mouvements->whereIn('type', ['pret', 'depot'])->sum('quantite')
                            - (int) $mouvements->where('type', 'retour')->sum('quantite')
                            - (int) $mouvements->where('type', 'perdu')->sum('quantite'),
                        );
                    })
                    ->sortable(false),

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
