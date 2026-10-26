<?php

namespace App\Filament\Admin\Resources\Produits\Pages;

use App\Filament\Admin\Resources\Produits\ProduitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProduits extends ListRecords
{
    protected static string $resource = ProduitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
