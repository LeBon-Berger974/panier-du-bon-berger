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
                ->maxLength(255)
                ->disabled(fn (): bool => ! auth()->user()?->canUsePermission('products.manage')),

            FileUpload::make('photo')
                ->label('Photo du produit')
                ->image()
                ->disk('public')
                ->directory('produits')
                ->visibility('public')
                ->imageEditor()
                ->maxSize(5120)
                ->disabled(fn (): bool => ! auth()->user()?->canUsePermission('products.manage')),

            TextInput::make('unite')
                ->label('Unité')
                ->required()
                ->maxLength(100)
                ->placeholder('À renseigner')
                ->disabled(fn (): bool => ! auth()->user()?->canUsePermission('products.manage')),

            TextInput::make('saison')
                ->label('Saison')
                ->maxLength(100)
                ->disabled(fn (): bool => ! auth()->user()?->canUsePermission('products.manage')),

            TextInput::make('stock')
                ->label('Stock disponible')
                ->numeric()
                ->minValue(0)
                ->default(5)
                ->required()
                ->disabled(fn (): bool => ! auth()->user()?->canUsePermission('stock.update')),

            TextInput::make('seuil_alerte')
                ->label('Seuil d’alerte du stock')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required()
                ->disabled(fn (): bool => ! auth()->user()?->canUsePermission('products.manage')),

            Toggle::make('actif')
                ->label('Produit actif')
                ->default(true)
                ->disabled(fn (): bool => ! auth()->user()?->canUsePermission('products.toggle')),
        ]);
    }
}
