<?php

namespace App\Models\Concerns;

trait CanAccessAdmin
{
    public function canAccessAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}
