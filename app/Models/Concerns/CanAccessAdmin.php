<?php

namespace App\Models\Concerns;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

trait CanAccessAdmin
{
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin';
    }
}
