<?php

namespace App\Filament\Admin\Resources\Produits\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProduitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nom')
                ->label('Nom du produit')
                ->required()
                ->maxLength(255),

            FileUpload::make('photo')
                ->label('Photo du produit')
                ->image()
                ->disk('public')
                ->directory('produits')
                ->visibility('public')
                ->imageEditor()
                ->maxSize(5120),

            TextInput::make('unite')
                ->label('Unité')
                ->required()
                ->maxLength(100),

            TextInput::make('saison')
                ->label('Saison')
                ->maxLength(100),

            TextInput::make('stock')
                ->label('Stock disponible')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),

            TextInput::make('seuil_alerte')
                ->label('Seuil d’alerte du stock')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),

            Toggle::make('actif')
                ->label('Produit actif')
                ->default(true),
        ]);
    }
}