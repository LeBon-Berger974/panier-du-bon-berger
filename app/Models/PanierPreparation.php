<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property array<int, array<string, mixed>> $composition */
class PanierPreparation extends Model
{
    protected $fillable = [
        'panier_id',
        'panier_nom',
        'nombre',
        'composition',
        'annule_at',
    ];

    protected function casts(): array
    {
        return [
            'nombre' => 'integer',
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
