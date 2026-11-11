<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecompenseRelais extends Model
{
    protected $table = 'recompenses_relais';

    protected $fillable = [
        'point_relais_id', 'session_livraison_id', 'numero_palier',
        'nombre_paniers_offerts', 'statut', 'remis_at',
    ];

    protected function casts(): array
    {
        return [
            'numero_palier' => 'integer',
            'nombre_paniers_offerts' => 'integer',
            'remis_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PointRelais, $this> */
    public function pointRelais(): BelongsTo
    {
        return $this->belongsTo(PointRelais::class);
    }

    /** @return BelongsTo<SessionLivraison, $this> */
    public function sessionLivraison(): BelongsTo
    {
        return $this->belongsTo(SessionLivraison::class);
    }
}
