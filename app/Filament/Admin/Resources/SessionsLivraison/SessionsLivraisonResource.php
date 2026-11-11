<?php

namespace App\Filament\Admin\Resources\SessionsLivraison;

use App\Filament\Admin\Resources\SessionsLivraison\Pages\CreateSessionLivraison;
use App\Filament\Admin\Resources\SessionsLivraison\Pages\EditSessionLivraison;
use App\Filament\Admin\Resources\SessionsLivraison\Pages\ListSessionsLivraison;
use App\Filament\Admin\Resources\SessionsLivraison\Schemas\SessionLivraisonForm;
use App\Filament\Admin\Resources\SessionsLivraison\Tables\SessionsLivraisonTable;
use App\Models\SessionLivraison;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SessionsLivraisonResource extends Resource
{
    protected static ?string $model = SessionLivraison::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Livraisons';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'livraison';

    protected static ?string $pluralModelLabel = 'livraisons';

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
        return parent::getEloquentQuery()->with(['configurations.produits.produit']);
    }

    public static function form(Schema $schema): Schema
    {
        return SessionLivraisonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SessionsLivraisonTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSessionsLivraison::route('/'),
            'create' => CreateSessionLivraison::route('/create'),
            'edit' => EditSessionLivraison::route('/{record}/edit'),
        ];
    }
}
