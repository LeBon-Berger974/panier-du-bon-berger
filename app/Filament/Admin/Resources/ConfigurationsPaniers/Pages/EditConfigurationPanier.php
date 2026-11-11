<?php

namespace App\Filament\Admin\Resources\ConfigurationsPaniers\Pages;

use App\Filament\Admin\Resources\ConfigurationsPaniers\ConfigurationPanierResource;
use App\Models\ConfigurationPanier;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditConfigurationPanier extends EditRecord
{
    protected static ?string $title = 'Modifier le panier';

    protected static string $resource = ConfigurationPanierResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label('Supprimer le panier')->requiresConfirmation()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var ConfigurationPanier $panier */
        $panier = $this->getRecord();
        $data['composition'] = $panier->produits()
            ->where('actif', true)
            ->get()
            ->map(fn ($ligne): array => [
                'produit_id' => $ligne->produit_id,
                'quantite' => $ligne->quantite,
            ])
            ->all();

        return $data;
    }

    /** @var array<int, array{produit_id: int|string, quantite: int|float|string}> */
    protected array $compositionDuPanier = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->compositionDuPanier = $data['composition'] ?? [];
        unset($data['composition']);

        return $data;
    }

    protected function afterSave(): void
    {
        $panier = $this->getRecord();
        $panier->produits()->delete();
        $panier->produits()->createMany(array_map(fn (array $ligne): array => [
            'produit_id' => $ligne['produit_id'],
            'quantite' => $ligne['quantite'] ?? 1,
            'actif' => true,
        ], $this->compositionDuPanier));
    }
}
