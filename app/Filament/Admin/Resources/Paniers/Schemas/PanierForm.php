<?php

namespace App\Filament\Admin\Resources\Paniers\Schemas;

use App\Models\Produit;
use Filament\Facades\Filament;
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
                ->maxLength(255)
                ->disabled(fn (): bool => ! auth()->user()?->canUsePermission('baskets.manage')),

            Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->columnSpanFull()
                ->disabled(fn (): bool => ! auth()->user()?->canUsePermission('baskets.manage')),

            FileUpload::make('photo')
                ->label('Photo du panier')
                ->image()
                ->disk('public')
                ->directory('paniers')
                ->visibility('public')
                ->imageEditor()
                ->maxSize(5120)
                ->disabled(fn (): bool => ! auth()->user()?->canUsePermission('baskets.manage')),

            TextInput::make('prix')
                ->label('Prix de vente (€)')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required()
                ->disabled(fn (): bool => ! auth()->user()?->canUsePermission('baskets.manage')),

            TextInput::make('stock')
                ->label('Nombre de paniers préparés')
                ->numeric()
                ->integer()
                ->minValue(0)
                ->default(0)
                ->required()
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'
                    && auth()->user()?->hasRole('admin')),

            Toggle::make('actif')
                ->label('Panier disponible à la vente')
                ->default(true)
                ->disabled(fn (): bool => ! auth()->user()?->canUsePermission('baskets.manage')),

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
                ->columnSpanFull()
                ->disabled(fn (): bool => ! auth()->user()?->canUsePermission('baskets.manage')),
        ]);
    }
}
