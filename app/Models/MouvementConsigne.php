<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MouvementConsigne extends Model
{
    protected $table = 'mouvements_consignes';

    protected $fillable = [
        'commande_id', 'type', 'quantite', 'montant_unitaire', 'effectue_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'montant_unitaire' => 'decimal:2',
            'effectue_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Commande, $this> */
    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }
}
