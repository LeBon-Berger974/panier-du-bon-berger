<?php

namespace App\Filament\Admin\Resources\Paniers\Schemas;

use App\Models\Produit;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PanierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nom')
                ->label('Nom du panier')
                ->required()
                ->maxLength(255),

            Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->columnSpanFull(),

            FileUpload::make('photo')
                ->label('Photo du panier')
                ->image()
                ->disk('public')
                ->directory('paniers')
                ->visibility('public')
                ->imageEditor()
                ->maxSize(5120),

            TextInput::make('prix')
                ->label('Prix de vente (€)')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),

            TextInput::make('stock')
                ->label('Nombre de paniers préparés')
                ->numeric()
                ->integer()
                ->minValue(0)
                ->default(0)
                ->required(),

            Toggle::make('actif')
                ->label('Panier disponible à la vente')
                ->default(true),

            Repeater::make('composition')
                ->label('Composition du panier')
                ->schema([
                    Select::make('produit_id')
                        ->label('Produit')
                        ->options(fn (): array => Produit::query()
                            ->where('actif', true)
                            ->orderBy('nom')
                            ->pluck('nom', 'id')
                            ->all())
                        ->searchable()
                        ->required(),

                    TextInput::make('quantite')
                        ->label('Quantité')
                        ->numeric()
                        ->minValue(0.01)
                        ->required(),
                ])
                ->columns(2)
                ->defaultItems(0)
                ->addActionLabel('Ajouter un produit')
                ->reorderable(false)
                ->columnSpanFull(),
        ]);
    }
}