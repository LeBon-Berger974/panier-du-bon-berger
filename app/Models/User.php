<?php

namespace App\Models;

use App\Models\Concerns\CanAccessAdmin;
use Database\Factories\UserFactory;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/** @property array<int, string>|null $permissions */
#[Fillable(['name', 'email', 'password', 'permissions', 'point_relais_favori_id', 'google_id', 'google_avatar_url', 'avatar_path'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use CanAccessAdmin, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Liste des permissions disponibles pour les préparateurs.
     */
    public const PREPARATEUR_PERMISSIONS = [
        'baskets.view' => 'Consulter les paniers',
        'baskets.manage' => 'Créer et modifier les paniers',
        'deliveries.view' => 'Consulter les livraisons et configurations',
        'deliveries.manage' => 'Gérer les livraisons et publier les configurations',
        'reservations.create' => 'Ajouter des réservations',
        'preparations.view' => 'Consulter l’historique des préparations',
        'preparations.create' => 'Préparer des paniers',
        'preparations.cancel' => 'Annuler une préparation',
        'products.view' => 'Consulter les produits',
        'products.manage' => 'Créer et modifier les produits',
        'stock.update' => 'Modifier les stocks',
        'products.toggle' => 'Activer ou désactiver un produit',
    ];

    /** @return BelongsToMany<Role, $this, Pivot, 'pivot'> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    /** @return HasMany<Commande, $this> */
    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class, 'user_id');
    }

    /** @return BelongsTo<PointRelais, $this> */
    public function pointRelaisFavori(): BelongsTo
    {
        return $this->belongsTo(PointRelais::class, 'point_relais_favori_id');
    }

    /** @return BelongsToMany<PointRelais, $this, Pivot, 'pivot'> */
    public function pointsRelaisAffectes(): BelongsToMany
    {
        return $this->belongsToMany(PointRelais::class, 'point_relais_user', 'user_id', 'point_relais_id');
    }

    public function canConsulterCommandesRelais(): bool
    {
        return $this->hasRole('responsable_relais') && $this->pointsRelaisAffectes()->exists();
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function hasAnyRole(string ...$roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Vérifie si l'utilisateur possède une permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }

    public function canUsePermission(string $permission): bool
    {
        $panel = Filament::getCurrentPanel()?->getId();

        return match ($panel) {
            'admin' => $this->hasRole('admin'),

            'preparateur' => ($this->hasRole('preparateur') && $this->hasPermission($permission))
                || ($permission === 'relay.orders.view' && $this->canConsulterCommandesRelais())
                || ($permission === 'relay.pickup' && $this->canConsulterCommandesRelais()),

            default => false,
        };
    }

    /**
     * Vérifie si l'utilisateur possède au moins une permission.
     */
    public function hasAnyPermission(): bool
    {
        return ! empty($this->permissions);
    }

    /**
     * Retourne les permissions disponibles.
     */
    /** @return array<string, string> */
    public static function preparateurPermissions(): array
    {
        return self::PREPARATEUR_PERMISSIONS;
    }

    /**
     * Attribue un ou plusieurs rôles sans supprimer les autres.
     */
    public function assignRole(string|Role ...$roles): static
    {
        $roleIds = [];

        foreach ($roles as $role) {
            $roleModel = $role instanceof Role
                ? $role
                : Role::where('name', $role)->firstOrFail();

            $roleIds[] = $roleModel->id;
        }

        $this->roles()->syncWithoutDetaching($roleIds);

        return $this;
    }

    /**
     * Retire un rôle en protégeant le dernier administrateur.
     */
    public function removeRole(string|Role $role): static
    {
        DB::transaction(function () use ($role) {
            $roleModel = $role instanceof Role
                ? $role
                : Role::where('name', $role)->firstOrFail();

            if ($roleModel->name === 'admin' && $this->hasRole('admin')) {
                Role::whereKey($roleModel->id)->lockForUpdate()->first();

                $adminCount = DB::table('role_user')
                    ->where('role_id', $roleModel->id)
                    ->count();

                if ($adminCount <= 1) {
                    throw ValidationException::withMessages([
                        'roles' => 'Impossible de retirer le dernier administrateur.',
                    ]);
                }
            }

            $this->roles()->detach($roleModel->id);
        });

        return $this;
    }

    /**
     * Synchronise les rôles sélectionnés par l'administrateur.
     */
    /** @param array<int, int|string> $roleIds */
    public function syncRoles(array $roleIds): static
    {
        DB::transaction(function () use ($roleIds) {
            $adminRole = Role::where('name', 'admin')
                ->lockForUpdate()
                ->first();

            if (! $adminRole) {
                throw ValidationException::withMessages([
                    'roles' => 'Le rôle administrateur est introuvable.',
                ]);
            }

            $roleIds = array_values(array_unique(array_map('intval', $roleIds)));

            $existingRolesCount = Role::whereIn('id', $roleIds)->count();

            if ($existingRolesCount !== count($roleIds)) {
                throw ValidationException::withMessages([
                    'roles' => 'Un ou plusieurs rôles sélectionnés sont invalides.',
                ]);
            }

            $currentlyAdmin = $this->roles()
                ->where('roles.id', $adminRole->id)
                ->exists();

            $willBeAdmin = in_array($adminRole->id, $roleIds, true);

            if ($currentlyAdmin && ! $willBeAdmin) {
                if (auth()->id() === $this->id) {
                    throw ValidationException::withMessages([
                        'roles' => 'Vous ne pouvez pas retirer votre propre rôle administrateur.',
                    ]);
                }

                $adminCount = DB::table('role_user')
                    ->where('role_id', $adminRole->id)
                    ->count();

                if ($adminCount <= 1) {
                    throw ValidationException::withMessages([
                        'roles' => 'Impossible de retirer le dernier administrateur.',
                    ]);
                }
            }

            $this->roles()->sync($roleIds);
        });

        return $this;
    }

    /**
     * Attribue les permissions et synchronise automatiquement
     * le rôle préparateur.
     */
    /** @param array<int, mixed> $permissions */
    public function syncPermissions(array $permissions): static
    {
        $permissions = array_values(array_unique($permissions));

        foreach ($permissions as $permission) {
            if (
                ! is_string($permission) ||
                ! array_key_exists($permission, self::PREPARATEUR_PERMISSIONS)
            ) {
                throw ValidationException::withMessages([
                    'permissions' => 'Une ou plusieurs permissions sélectionnées sont invalides.',
                ]);
            }
        }

        DB::transaction(function () use ($permissions) {
            $this->newQuery()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->forceFill([
                'permissions' => $permissions,
            ])->save();

            $preparateurRole = Role::where('name', 'preparateur')->first();
            $clientRole = Role::where('name', 'client')->first();

            if (! empty($permissions)) {
                if (! $preparateurRole) {
                    throw ValidationException::withMessages([
                        'permissions' => 'Le rôle préparateur est introuvable.',
                    ]);
                }

                $this->roles()->syncWithoutDetaching([
                    $preparateurRole->id,
                ]);

                return;
            }

            if (
                $preparateurRole &&
                $this->roles()->where('roles.id', $preparateurRole->id)->exists()
            ) {
                $this->roles()->detach($preparateurRole->id);

                if (! $this->hasRole('client')) {
                    if (! $clientRole) {
                        throw ValidationException::withMessages([
                            'roles' => 'Le rôle client est introuvable.',
                        ]);
                    }

                    $this->roles()->syncWithoutDetaching([
                        $clientRole->id,
                    ]);
                }
            }
        });

        return $this;
    }

    /**
     * Contrôle l'accès aux panneaux Filament.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->hasRole('admin'),

            'preparateur' => ($this->hasRole('preparateur') && $this->hasAnyPermission())
                || $this->canConsulterCommandesRelais(),

            default => false,
        };
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'permissions' => 'array',
        ];
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatarUrl();
    }

    public function avatarUrl(): ?string
    {
        return filled($this->avatar_path)
            ? Storage::disk('public')->url($this->avatar_path)
            : null;
    }

    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
