<?php

namespace App\Filament\Admin\Resources\ConfigurationsPaniers;

use App\Filament\Admin\Resources\ConfigurationsPaniers\Pages\CreateConfigurationPanier;
use App\Filament\Admin\Resources\ConfigurationsPaniers\Pages\EditConfigurationPanier;
use App\Filament\Admin\Resources\ConfigurationsPaniers\Pages\ListConfigurationsPaniers;
use App\Filament\Admin\Resources\ConfigurationsPaniers\Schemas\ConfigurationPanierForm;
use App\Filament\Admin\Resources\ConfigurationsPaniers\Tables\ConfigurationsPaniersTable;
use App\Models\ConfigurationPanier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ConfigurationPanierResource extends Resource
{
    protected static ?string $model = ConfigurationPanier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Paniers';

    protected static ?string $slug = 'paniers';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'panier';

    protected static ?string $pluralModelLabel = 'paniers';

    protected static ?string $recordTitleAttribute = 'nom';

    public static function canViewAny(): bool
    {
        return (auth()->user()?->canUsePermission('deliveries.view') ?? false)
            || (auth()->user()?->canUsePermission('deliveries.manage') ?? false);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canUsePermission('deliveries.manage') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->canUsePermission('deliveries.manage') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canUsePermission('deliveries.manage') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['sessions', 'produits.produit']);
    }

    public static function form(Schema $schema): Schema
    {
        return ConfigurationPanierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConfigurationsPaniersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConfigurationsPaniers::route('/'),
            'create' => CreateConfigurationPanier::route('/create'),
            'edit' => EditConfigurationPanier::route('/{record}/edit'),
        ];
    }
}
