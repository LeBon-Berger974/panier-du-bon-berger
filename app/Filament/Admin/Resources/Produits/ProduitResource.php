<?php

namespace App\Filament\Admin\Resources\Produits;

use App\Filament\Admin\Resources\Produits\Pages\CreateProduit;
use App\Filament\Admin\Resources\Produits\Pages\EditProduit;
use App\Filament\Admin\Resources\Produits\Pages\ListProduits;
use App\Filament\Admin\Resources\Produits\Schemas\ProduitForm;
use App\Filament\Admin\Resources\Produits\Tables\ProduitsTable;
use App\Models\Produit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProduitResource extends Resource
{
    protected static ?string $model = Produit::class;
    
    protected static ?string $navigationLabel = 'Produits et stock';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nom';

    public static function form(Schema $schema): Schema
    {
        return ProduitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProduitsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProduits::route('/'),
            'create' => CreateProduit::route('/create'),
            'edit' => EditProduit::route('/{record}/edit'),
        ];
    }
}
