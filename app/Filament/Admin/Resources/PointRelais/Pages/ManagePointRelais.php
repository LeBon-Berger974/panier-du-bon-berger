<?php

namespace App\Filament\Admin\Resources\PointRelais\Pages;

use App\Filament\Admin\Resources\PointRelais\PointRelaisResource;
use App\Filament\Admin\Widgets\DemandesPointRelaisWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePointRelais extends ManageRecords
{
    protected static string $resource = PointRelaisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [DemandesPointRelaisWidget::class];
    }
}
