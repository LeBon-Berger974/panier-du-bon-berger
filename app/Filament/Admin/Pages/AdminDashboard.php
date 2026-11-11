<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard;

class AdminDashboard extends Dashboard
{
    protected static ?string $title = 'Tableau de bord';

    protected static ?string $navigationLabel = 'Tableau de bord';

    public function getColumns(): int|array
    {
        return 1;
    }
}
