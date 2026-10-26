<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Panier extends Model
{
    protected $fillable = [
        'nom',
        'description',
        'photo',
        'prix',
        'stock',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'prix' => 'decimal:2',
            'stock' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function produits(): BelongsToMany
    {
        return $this->belongsToMany(
            Produit::class,
            'panier_produits'
        )->withPivot('quantite')->withTimestamps();
    }
}