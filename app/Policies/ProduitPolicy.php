<?php

namespace App\Policies;

use App\Models\Produit;
use App\Models\User;
use Filament\Facades\Filament;

class ProduitPolicy
{
    private function isAdmin(User $user): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin'
            && $user->hasRole('admin');
    }

    public function viewAny(User $user): bool
    {
        return $user->canUsePermission('products.view')
            || $user->canUsePermission('products.manage')
            || $user->canUsePermission('stock.update')
            || $user->canUsePermission('products.toggle');
    }

    public function view(User $user, Produit $produit): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->canUsePermission('products.manage');
    }

    public function update(User $user, Produit $produit): bool
    {
        return $user->canUsePermission('products.manage')
            || $user->canUsePermission('stock.update')
            || $user->canUsePermission('products.toggle');
    }

    public function delete(User $user, Produit $produit): bool
    {
        return $this->isAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }
}
