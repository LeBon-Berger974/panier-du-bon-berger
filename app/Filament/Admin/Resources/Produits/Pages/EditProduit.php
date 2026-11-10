<?php

namespace App\Filament\Admin\Resources\Produits\Pages;

use App\Filament\Admin\Resources\Produits\ProduitResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduit extends EditRecord
{
    protected static string $resource = ProduitResource::class;

    protected function handleRecordUpdate(
        Model $record,
        array $data
    ): Model {
        $user = auth()->user();

        $allowedData = [];

        if ($user?->canUsePermission('products.manage')) {
            $allowedData = array_intersect_key($data, array_flip([
                'nom',
                'photo',
                'unite',
                'saison',
                'seuil_alerte',
            ]));
        }

        if (
            $user?->canUsePermission('stock.update')
            && array_key_exists('stock', $data)
        ) {
            $allowedData['stock'] = $data['stock'];
        }

        if (
            $user?->canUsePermission('products.toggle')
            && array_key_exists('actif', $data)
        ) {
            $allowedData['actif'] = $data['actif'];
        }

        if ($allowedData !== []) {
            $record->fill($allowedData);
            $record->save();
        }

        return $record->refresh();
    }
}
