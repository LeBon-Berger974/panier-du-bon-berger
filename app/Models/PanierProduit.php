<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PanierProduit extends Model
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

    public function panier(): BelongsTo
    {
        return $this->belongsTo(Panier::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
