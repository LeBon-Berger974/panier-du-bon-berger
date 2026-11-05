<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function panier(): BelongsTo
    {
        return $this->belongsTo(Panier::class);
    }
}
