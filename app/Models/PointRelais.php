<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PointRelais extends Model
{
    protected $table = 'points_relais';

    protected $fillable = [
        'nom', 'adresse', 'complement', 'code_postal', 'ville',
        'latitude', 'longitude', 'contact_nom', 'contact_telephone',
        'minimum_paniers', 'retrait_ferme', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'minimum_paniers' => 'integer',
            'retrait_ferme' => 'boolean',
            'actif' => 'boolean',
        ];
    }

    /** @return HasMany<CreneauRetrait, $this> */
    public function creneaux(): HasMany
    {
        return $this->hasMany(CreneauRetrait::class)->orderByRaw("CASE jour WHEN 'mercredi' THEN 0 WHEN 'vendredi' THEN 1 ELSE 2 END");
    }

    /** @return HasMany<Commande, $this> */
    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class);
    }

    public function getAdresseCompleteAttribute(): string
    {
        return trim($this->adresse.($this->complement ? ', '.$this->complement : '').', '.$this->code_postal.' '.$this->ville);
    }
}
