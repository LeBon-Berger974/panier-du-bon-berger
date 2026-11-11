<?php

namespace App\Filament\Admin\Widgets;

use App\Models\DemandePointRelais;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class DemandesPointRelaisWidget extends TableWidget
{
    protected static ?string $heading = 'Demandes reçues';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(DemandePointRelais::query()->latest())
            ->columns([
                TextColumn::make('nom_lieu')->label('Lieu ou groupe')->searchable(),
                TextColumn::make('contact')->label('Contact'),
                TextColumn::make('commune')->label('Commune')->searchable(),
                TextColumn::make('jour_souhaite')->label('Jour')->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('nombre_paniers_estime')->label('Paniers estimés'),
                SelectColumn::make('statut')->label('Suivi')->options([
                    'a_traiter' => 'À traiter',
                    'contactee' => 'Contactée',
                    'acceptee' => 'Acceptée',
                    'refusee' => 'Refusée',
                ])->afterStateUpdated(fn () => Notification::make()->title('Suivi de la demande mis à jour')->success()->send()),
                TextColumn::make('created_at')->label('Reçue le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
