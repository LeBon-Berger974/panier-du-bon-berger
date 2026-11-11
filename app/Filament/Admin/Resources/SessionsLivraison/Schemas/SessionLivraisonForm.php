<?php

namespace App\Filament\Admin\Resources\SessionsLivraison\Schemas;

use App\Models\ConfigurationPanier;
use App\Models\ReglagesSite;
use App\Rules\DeliveryDayRule;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
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
            Section::make('Paniers proposés')
                ->description('Choisissez les paniers déjà créés qui seront proposés pour cette livraison. Vous pouvez en sélectionner plusieurs.')
                ->schema([
                    Select::make('configurations_selectionnees')
                        ->label('Paniers')
                        ->options(fn (): array => ConfigurationPanier::query()->orderBy('nom')->get()
                            ->mapWithKeys(fn (ConfigurationPanier $configuration): array => [
                                $configuration->id => $configuration->nom.' · '.number_format((float) $configuration->prix_ht, 2, ',', ' ').' € HT',
                            ])
                            ->all())
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->helperText('Utilisez la recherche pour trouver un panier par son nom.'),
                ]),
        ]);
    }
}
