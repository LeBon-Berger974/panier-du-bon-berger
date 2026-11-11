<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property array<int, array{produit_id: int, nom?: string, quantite?: float}>|null $composition */
class PanierReservation extends Model
{
    protected $fillable = [
        'panier_id',
        'nombre',
        'nombre_prepare',
        'statut',
        'composition',
        'annule_at',
    ];

    protected function casts(): array
    {
        return [
            'nombre' => 'integer',
            'nombre_prepare' => 'integer',
            'composition' => 'array',
            'annule_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Panier, $this> */
    public function panier(): BelongsTo
    {
        return $this->belongsTo(Panier::class);
    }
}
