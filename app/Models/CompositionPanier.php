<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompositionPanier extends Model
{
    protected $fillable = [
        'session_livraison_id',
        'produit_id',
        'quantite',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
        ];
    }

    /** @return BelongsTo<SessionLivraison, $this> */
    public function sessionLivraison(): BelongsTo
    {
        return $this->belongsTo(SessionLivraison::class, 'session_livraison_id');
    }

    /** @return BelongsTo<Produit, $this> */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
