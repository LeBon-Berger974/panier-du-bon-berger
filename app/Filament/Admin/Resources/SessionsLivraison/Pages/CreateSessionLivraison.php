<?php

namespace App\Filament\Admin\Resources\SessionsLivraison\Pages;

use App\Filament\Admin\Resources\SessionsLivraison\SessionsLivraisonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSessionLivraison extends CreateRecord
{
    protected static ?string $title = 'Créer une livraison';

    protected static string $resource = SessionsLivraisonResource::class;

    /** @var array<int, int|string> */
    protected array $configurationsSelectionnees = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->configurationsSelectionnees = $data['configurations_selectionnees'] ?? [];
        unset($data['configurations_selectionnees']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->getRecord()->configurations()->sync($this->configurationsSelectionnees);
    }
}
