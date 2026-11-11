<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreneauRetrait extends Model
{
    protected $table = 'creneaux_retrait';

    protected $fillable = ['point_relais_id', 'jour', 'heure_debut', 'heure_fin'];

    /** @return BelongsTo<PointRelais, $this> */
    public function pointRelais(): BelongsTo
    {
        return $this->belongsTo(PointRelais::class);
    }

    public function getResumeAttribute(): string
    {
        return ucfirst($this->jour).' '.substr((string) $this->heure_debut, 0, 5).'–'.substr((string) $this->heure_fin, 0, 5);
    }
}
