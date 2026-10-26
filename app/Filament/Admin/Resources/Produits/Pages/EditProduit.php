<?php

namespace App\Filament\Admin\Resources\Produits\Pages;

use App\Filament\Admin\Resources\Produits\ProduitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduit extends EditRecord
{
    protected static string $resource = ProduitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
