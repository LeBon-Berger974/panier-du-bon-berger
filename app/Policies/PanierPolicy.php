<?php

namespace App\Policies;

use App\Models\Panier;
use App\Models\User;
use Filament\Facades\Filament;

class PanierPolicy
{
    private function allowed(User $user, string $permission): bool
    {
        return $user->canUsePermission($permission);
    }

    private function isAdmin(User $user): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin'
            && $user->hasRole('admin');
    }

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'baskets.view')
            || $this->allowed($user, 'reservations.create')
            || $this->allowed($user, 'preparations.view')
            || $this->allowed($user, 'preparations.create')
            || $this->allowed($user, 'preparations.cancel');
    }

    public function view(User $user, Panier $panier): bool
    {
        return $this->allowed($user, 'baskets.view')
            || $this->allowed($user, 'reservations.create')
            || $this->allowed($user, 'preparations.view')
            || $this->allowed($user, 'preparations.create')
            || $this->allowed($user, 'preparations.cancel');
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'baskets.manage');
    }

    public function update(User $user, Panier $panier): bool
    {
        return $this->allowed($user, 'baskets.manage')
            || $this->allowed($user, 'preparations.view')
            || $this->allowed($user, 'preparations.create')
            || $this->allowed($user, 'preparations.cancel');
    }

    public function delete(User $user, Panier $panier): bool
    {
        return $this->isAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }
}
