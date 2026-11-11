<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandePointRelais extends Model
{
    protected $table = 'demandes_point_relais';

    protected $fillable = [
        'nom_lieu', 'contact', 'commune', 'jour_souhaite', 'nombre_paniers_estime', 'statut',
    ];

    protected function casts(): array
    {
        return ['nombre_paniers_estime' => 'integer'];
    }
}
