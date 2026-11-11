<?php

namespace App\Filament\Admin\Resources\PointRelais;

use App\Filament\Admin\Resources\PointRelais\Pages\ManagePointRelais;
use App\Models\PointRelais;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PointRelaisResource extends Resource
{
    protected static ?string $model = PointRelais::class;

    protected static ?string $navigationLabel = 'Points relais';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'point relais';

    protected static ?string $pluralModelLabel = 'points relais';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $recordTitleAttribute = 'nom';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Lieu de retrait')->columns(2)->schema([
                TextInput::make('nom')->label('Nom du relais')->required()->maxLength(255),
                TextInput::make('adresse')->label('Adresse')->required()->maxLength(255),
                TextInput::make('complement')->label('Complément')->maxLength(255),
                TextInput::make('code_postal')->label('Code postal')->required()->maxLength(12),
                TextInput::make('ville')->label('Ville')->required()->maxLength(255),
                TextInput::make('latitude')->label('Latitude')->numeric()->minValue(-90)->maxValue(90),
                TextInput::make('longitude')->label('Longitude')->numeric()->minValue(-180)->maxValue(180),
                TextInput::make('contact_nom')->label('Nom du contact')->maxLength(255),
                TextInput::make('contact_telephone')->label('Téléphone du contact')->tel()->maxLength(30),
                TextInput::make('minimum_paniers')->label('Minimum de paniers par livraison')->integer()->minValue(1)->default(5)->required(),
                Toggle::make('retrait_ferme')->label('Retrait à la ferme')->default(false),
                Toggle::make('actif')->label('Disponible à la réservation')->default(true),
            ]),
            Section::make('Créneaux de retrait')->schema([
                Repeater::make('creneaux')->relationship()->label('Jours et horaires')->schema([
                    Select::make('jour')->label('Jour de livraison')->options(['mercredi' => 'Mercredi', 'vendredi' => 'Vendredi'])->required()->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                    TimePicker::make('heure_debut')->label('À partir de')->seconds(false)->required(),
                    TimePicker::make('heure_fin')->label('Jusqu’à')->seconds(false)->required()->rule('after:heure_debut'),
                ])->columns(3)->addActionLabel('Ajouter un jour')->reorderable(false),
            ])->visible(fn ($record): bool => ! $record?->retrait_ferme),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('nom')->label('Point relais')->searchable()->sortable(),
            TextColumn::make('adresse_complete')->label('Adresse')->searchable(query: fn ($query, string $search) => $query->where('adresse', 'like', "%{$search}%")->orWhere('ville', 'like', "%{$search}%"))->wrap(),
            TextColumn::make('creneaux')->label('Jours')->state(fn (PointRelais $record): string => $record->creneaux->pluck('jour')->unique()->map(fn (string $jour): string => ucfirst($jour))->join(', '))->placeholder('À renseigner'),
            TextColumn::make('creneaux_horaires')->label('Horaire')->state(fn (PointRelais $record): string => $record->creneaux->map(fn ($creneau): string => substr((string) $creneau->heure_debut, 0, 5).'–'.substr((string) $creneau->heure_fin, 0, 5))->join(' · '))->placeholder('À renseigner'),
            TextColumn::make('contact_nom')->label('Contact')->formatStateUsing(fn (?string $state, PointRelais $record): string => trim(($state ?? '').($record->contact_telephone ? ' · '.$record->contact_telephone : '')) ?: '—'),
            TextColumn::make('minimum_paniers')->label('Minimum par livraison'),
            IconColumn::make('retrait_ferme')->label('Ferme')->boolean(),
            IconColumn::make('actif')->label('Disponible')->boolean(),
        ])->defaultSort('ville')->recordActions([EditAction::make()->label('Modifier')]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePointRelais::route('/')];
    }
}
