<?php

namespace App\Filament\Admin\Resources\SessionsLivraison\Schemas;

use App\Models\Produit;
use App\Models\ReglagesSite;
use App\Rules\DeliveryDayRule;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SessionLivraisonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Créneau de livraison')
                ->columns(2)
                ->schema([
                    DatePicker::make('date_livraison')
                        ->label('Date de livraison')
                        ->native(false)
                        ->required()
                        ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                            if (! filled($state)) {
                                return;
                            }

                            $jour = Carbon::parse($state)->dayOfWeek;
                            $suffixe = match ($jour) {
                                Carbon::WEDNESDAY => 'mercredi',
                                Carbon::FRIDAY => 'vendredi',
                                default => null,
                            };

                            if (! $suffixe) {
                                return;
                            }

                            $reglages = ReglagesSite::valeurs();
                            foreach ([
                                'ouverture_reservations_at' => "ouverture_reservations_{$suffixe}",
                                'cloture_reservations_at' => "cloture_reservations_{$suffixe}",
                            ] as $champ => $cleReglage) {
                                $heure = $reglages[$cleReglage] ?? null;
                                if (! filled($get($champ)) && filled($heure)) {
                                    $set($champ, $state.' '.substr((string) $heure, 0, 5));
                                }
                            }
                        })
                        ->rules([new DeliveryDayRule]),

                    TextInput::make('jour')
                        ->label('Jour de livraison')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Calculé depuis la date'),

                    DateTimePicker::make('ouverture_reservations_at')
                        ->label('Ouverture des réservations')
                        ->native(false)
                        ->required(),

                    DateTimePicker::make('cloture_reservations_at')
                        ->label('Clôture des réservations')
                        ->native(false)
                        ->required()
                        ->rule('after:ouverture_reservations_at')
                        ->rule('before:date_livraison')
                        ->validationMessages([
                            'before' => 'La clôture des réservations doit précéder le jour de livraison.',
                        ]),

                    TextInput::make('capacite_max')
                        ->label('Capacité maximale (paniers)')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required(),
                ]),

            Section::make('Composition du panier de cette livraison')
                ->description('La composition est enregistrée pour cette session et peut varier d’une livraison à l’autre.')
                ->schema([
                    Repeater::make('compositions')
                        ->relationship()
                        ->label('Produits et quantités par panier')
                        ->required()
                        ->minItems(1)
                        ->schema([
                            Select::make('produit_id')
                                ->label('Produit')
                                ->options(fn (): array => Produit::query()
                                    ->where('actif', true)
                                    ->where('stock', '>', 0)
                                    ->orderBy('nom')
                                    ->pluck('nom', 'id')
                                    ->all())
                                ->searchable()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->required(),

                            TextInput::make('quantite')
                                ->label('Quantité par panier')
                                ->numeric()
                                ->minValue(0.001)
                                ->required(),
                        ])
                        ->columns(2)
                        ->addActionLabel('Ajouter un produit')
                        ->reorderable(false),
                ]),
        ]);
    }
}
