<?php

namespace App\Filament\Admin\Resources\Paniers;

use App\Filament\Admin\Resources\Paniers\Pages\CreatePanier;
use App\Filament\Admin\Resources\Paniers\Pages\EditPanier;
use App\Filament\Admin\Resources\Paniers\Pages\ListPaniers;
use App\Filament\Admin\Resources\Paniers\Schemas\PanierForm;
use App\Filament\Admin\Resources\Paniers\Tables\PaniersTable;
use App\Models\Panier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Produit;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;

class PanierResource extends Resource
{
    protected static ?string $model = Panier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nom';

    public static function form(Schema $schema): Schema
    {
        return PanierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaniersTable::configure($table);
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
            'index' => ListPaniers::route('/'),
            'create' => CreatePanier::route('/create'),
            'edit' => EditPanier::route('/{record}/edit'),
        ];
    }
}
