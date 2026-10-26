<?php

namespace App\Filament\Admin\Resources\Paniers\Pages;

use App\Filament\Admin\Resources\Paniers\PanierResource;
use App\Models\Panier;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreatePanier extends CreateRecord
{
    protected static string $resource = PanierResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $composition = $data['composition'] ?? [];

        unset($data['composition']);

        return DB::transaction(function () use ($data, $composition): Panier {
            $panier = Panier::create($data);

            $produits = [];

            foreach ($composition as $ligne) {
                if (! empty($ligne['produit_id'])) {
                    $produits[$ligne['produit_id']] = [
                        'quantite' => $ligne['quantite'],
                    ];
                }
            }

            $panier->produits()->sync($produits);

            return $panier;
        });
    }
}