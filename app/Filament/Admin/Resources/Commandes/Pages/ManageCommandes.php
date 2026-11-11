<?php

namespace App\Filament\Admin\Resources\Commandes\Pages;

use App\Filament\Admin\Resources\Commandes\CommandeResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCommandes extends ManageRecords
{
    protected static string $resource = CommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
