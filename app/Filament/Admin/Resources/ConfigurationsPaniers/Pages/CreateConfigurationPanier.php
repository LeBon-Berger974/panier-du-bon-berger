<?php

namespace App\Filament\Admin\Resources\ConfigurationsPaniers\Pages;

use App\Filament\Admin\Resources\ConfigurationsPaniers\ConfigurationPanierResource;
use Filament\Resources\Pages\CreateRecord;

class CreateConfigurationPanier extends CreateRecord
{
    protected static ?string $title = 'Créer un panier';

    protected static string $resource = ConfigurationPanierResource::class;

    /** @var array<int, array{produit_id: int|string, quantite: int|float|string}> */
    protected array $compositionDuPanier = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->compositionDuPanier = $data['composition'] ?? [];
        unset($data['composition']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->getRecord()->produits()->createMany(array_map(fn (array $ligne): array => [
            'produit_id' => $ligne['produit_id'],
            'quantite' => $ligne['quantite'] ?? 1,
            'actif' => true,
        ], $this->compositionDuPanier));
    }
}
