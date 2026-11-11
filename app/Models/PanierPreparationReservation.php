<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanierPreparationReservation extends Model
{
    protected $fillable = [
        'panier_preparation_id',
        'panier_reservation_id',
        'nombre',
    ];

    protected function casts(): array
    {
        return [
            'nombre' => 'integer',
        ];
    }

    /** @return BelongsTo<PanierPreparation, $this> */
    public function preparation(): BelongsTo
    {
        return $this->belongsTo(
            PanierPreparation::class,
            'panier_preparation_id'
        );
    }

    /** @return BelongsTo<PanierReservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(
            PanierReservation::class,
            'panier_reservation_id'
        );
    }
}
