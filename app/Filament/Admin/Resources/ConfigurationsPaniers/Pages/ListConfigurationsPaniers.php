<?php

namespace App\Filament\Admin\Resources\ConfigurationsPaniers\Pages;

use App\Filament\Admin\Resources\ConfigurationsPaniers\ConfigurationPanierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConfigurationsPaniers extends ListRecords
{
    protected static ?string $title = 'Paniers';

    protected static string $resource = ConfigurationPanierResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Créer un panier')];
    }
}
