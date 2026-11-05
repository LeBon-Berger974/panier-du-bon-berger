<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Produit $produit): void {
            // Un produit sans stock ne peut jamais rester réservable.
            // Le réapprovisionnement ne le réactive pas automatiquement.
            if ((float) $produit->stock <= 0) {
                $produit->actif = false;
            }
        });
    }
}
