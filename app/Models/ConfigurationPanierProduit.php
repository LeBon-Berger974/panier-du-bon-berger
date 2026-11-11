<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigurationPanierProduit extends Model
{
    protected $fillable = [
        'configuration_panier_id',
        'produit_id',
        'quantite',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
            'actif' => 'boolean',
        ];
    }

    /** @return BelongsTo<ConfigurationPanier, $this> */
    public function configurationPanier(): BelongsTo
    {
        return $this->belongsTo(ConfigurationPanier::class);
    }

    /** @return BelongsTo<Produit, $this> */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
