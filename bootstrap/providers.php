<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\PreparateurPanelProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    PreparateurPanelProvider::class,
    FortifyServiceProvider::class,
];
