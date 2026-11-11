<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property array<int, array{product_id: int, nom?: string, unite?: string|null, quantite_par_panier: float}>|null $composition */
class Commande extends Model
{
    public const A_PAYER = 'a_payer';

    public const PAYEE = 'payee';

    public const PREPAREE = 'preparee';

    public const AU_RELAIS = 'au_relais';

    public const RETIREE = 'retiree';

    public const ANNULEE = 'annulee';

    protected $fillable = [
        'reference', 'user_id', 'session_livraison_id', 'point_relais_id',
        'mode_retrait', 'nombre_paniers', 'prix_unitaire', 'montant_paniers',
        'montant_consigne', 'montant_total', 'montant_panier_ht', 'montant_consigne_ht', 'montant_tva', 'statut', 'composition',
        'payee_at', 'preparee_at', 'remise_relais_at', 'retiree_at', 'annulee_at', 'rappel_envoye_at',
    ];

    protected function casts(): array
    {
        return [
            'nombre_paniers' => 'integer',
            'prix_unitaire' => 'decimal:2',
            'montant_paniers' => 'decimal:2',
            'montant_consigne' => 'decimal:2',
            'montant_total' => 'decimal:2',
            'montant_panier_ht' => 'decimal:2',
            'montant_consigne_ht' => 'decimal:2',
            'montant_tva' => 'decimal:2',
            'composition' => 'array',
            'payee_at' => 'datetime',
            'preparee_at' => 'datetime',
            'remise_relais_at' => 'datetime',
            'retiree_at' => 'datetime',
            'annulee_at' => 'datetime',
            'rappel_envoye_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<SessionLivraison, $this> */
    public function sessionLivraison(): BelongsTo
    {
        return $this->belongsTo(SessionLivraison::class);
    }

    /** @return BelongsTo<PointRelais, $this> */
    public function pointRelais(): BelongsTo
    {
        return $this->belongsTo(PointRelais::class);
    }

    /** @return HasMany<Paiement, $this> */
    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    /** @return HasMany<MouvementConsigne, $this> */
    public function mouvementsConsignes(): HasMany
    {
        return $this->hasMany(MouvementConsigne::class);
    }

    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            self::A_PAYER => 'À payer',
            self::PAYEE => 'Payée',
            self::PREPAREE => 'Préparée',
            self::AU_RELAIS => 'Au point relais',
            self::RETIREE => 'Retirée',
            self::ANNULEE => 'Annulée',
            default => ucfirst($this->statut),
        };
    }
}
