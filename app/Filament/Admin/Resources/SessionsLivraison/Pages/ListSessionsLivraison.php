<?php

namespace App\Filament\Admin\Resources\SessionsLivraison\Pages;

use App\Filament\Admin\Resources\SessionsLivraison\SessionsLivraisonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSessionsLivraison extends ListRecords
{
    protected static ?string $title = 'Livraisons';

    protected static string $resource = SessionsLivraisonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouvelle livraison'),
        ];
    }
}
