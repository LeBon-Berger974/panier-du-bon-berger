<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ConfigurationPanier extends Model
{
    protected $table = 'configurations_paniers';

    protected $fillable = [
        'nom',
        'description',
        'photo',
        'prix_ht',
        'publie',
        'publication_mode',
        'publication_du',
        'publication_au',
    ];

    protected function casts(): array
    {
        return [
            'prix_ht' => 'decimal:2',
            'publie' => 'boolean',
            'publication_du' => 'date',
            'publication_au' => 'date',
        ];
    }

    /** @return BelongsToMany<SessionLivraison, $this> */
    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(SessionLivraison::class, 'configuration_panier_session_livraison')->withTimestamps();
    }

    /** @return HasMany<ConfigurationPanierProduit, $this> */
    public function produits(): HasMany
    {
        return $this->hasMany(ConfigurationPanierProduit::class);
    }

    /** @return HasMany<Commande, $this> */
    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class);
    }

    public function estVisibleSurSite(?Carbon $date = null): bool
    {
        $date ??= now();

        if ($this->publication_mode === 'plage') {
            return $this->publication_du !== null
                && $this->publication_au !== null
                && $this->publication_du->startOfDay()->lte($date)
                && $this->publication_au->endOfDay()->gte($date);
        }

        return $this->publie;
    }

    /** @param Builder<self> $query */
    public function scopeVisibleSurSite(Builder $query): Builder
    {
        $date = today()->toDateString();

        return $query->where(function (Builder $query) use ($date): void {
            $query->where(fn (Builder $manual) => $manual->where('publication_mode', 'manuel')->where('publie', true))
                ->orWhere(fn (Builder $schedule) => $schedule->where('publication_mode', 'plage')
                    ->whereNotNull('publication_du')->whereDate('publication_du', '<=', $date)
                    ->whereNotNull('publication_au')->whereDate('publication_au', '>=', $date));
        });
    }

    public function statutPublication(): string
    {
        if ($this->publication_mode !== 'plage') {
            return $this->publie ? 'Activé manuellement' : 'Désactivé';
        }

        if ($this->estVisibleSurSite()) {
            return 'Activé par la plage de dates';
        }

        if ($this->publication_du && now()->lt($this->publication_du->startOfDay())) {
            return 'Programmé à partir du '.$this->publication_du->format('d/m/Y');
        }

        return $this->publication_au ? 'Désactivé depuis le '.$this->publication_au->format('d/m/Y') : 'Plage de dates incomplète';
    }
}
