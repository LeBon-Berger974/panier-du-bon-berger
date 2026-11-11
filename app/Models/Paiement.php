<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    protected $fillable = [
        'commande_id', 'prestataire', 'reference_prestataire', 'statut',
        'montant_centimes', 'devise', 'confirme_at', 'donnees',
    ];

    protected function casts(): array
    {
        return [
            'montant_centimes' => 'integer',
            'confirme_at' => 'datetime',
            'donnees' => 'array',
        ];
    }

    /** @return BelongsTo<Commande, $this> */
    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }
}
