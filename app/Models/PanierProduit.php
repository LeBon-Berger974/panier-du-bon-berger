<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** @property string $quantite */
class PanierProduit extends Pivot
{
    protected $table = 'panier_produits';

    protected $fillable = [
        'panier_id',
        'produit_id',
        'quantite',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Panier, $this> */
    public function panier(): BelongsTo
    {
        return $this->belongsTo(Panier::class);
    }

    /** @return BelongsTo<Produit, $this> */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
