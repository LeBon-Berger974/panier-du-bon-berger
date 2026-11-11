<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @property-read PanierProduit $pivot */
class Produit extends Model
{
    protected $fillable = [
        'nom',
        'photo',
        'unite',
        'saison',
        'stock',
        'seuil_alerte',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'decimal:2',
            'seuil_alerte' => 'decimal:2',
            'actif' => 'boolean',
            'desactive_stock' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Produit $produit): void {
            if ((float) $produit->stock <= 0) {
                if ($produit->actif) {
                    $produit->desactive_stock = true;
                }
                $produit->actif = false;
            } elseif ($produit->isDirty('stock') && $produit->desactive_stock) {
                $produit->actif = true;
                $produit->desactive_stock = false;
            }
        });
    }
}
