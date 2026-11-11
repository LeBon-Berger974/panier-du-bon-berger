<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\ValidationException;

/**
 * @property-read \Illuminate\Support\Carbon $date_livraison
 * @property \Illuminate\Support\Carbon|null $ouverture_reservations_at
 * @property \Illuminate\Support\Carbon|null $cloture_reservations_at
 */
class SessionLivraison extends Model
{
    public const STATUT_BROUILLON = 'brouillon';

    public const STATUT_OUVERTE = 'ouverte';

    public const STATUT_CLOTUREE = 'cloturee';

    public const STATUT_LIVREE = 'livree';

    protected $table = 'sessions_livraison';

    protected $fillable = [
        'date_livraison',
        'jour',
        'ouverture_reservations_at',
        'cloture_reservations_at',
        'capacite_max',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_livraison' => 'date',
            'ouverture_reservations_at' => 'datetime',
            'cloture_reservations_at' => 'datetime',
            'capacite_max' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SessionLivraison $session): void {
            $date = Carbon::parse($session->date_livraison);

            $session->jour = match ($date->dayOfWeek) {
                Carbon::WEDNESDAY => 'mercredi',
                Carbon::FRIDAY => 'vendredi',
                default => throw ValidationException::withMessages([
                    'date_livraison' => 'Une livraison doit être prévue un mercredi ou un vendredi.',
                ]),
            };

            if (
                $session->ouverture_reservations_at &&
                $session->cloture_reservations_at &&
                $session->cloture_reservations_at->lessThanOrEqualTo($session->ouverture_reservations_at)
            ) {
                throw ValidationException::withMessages([
                    'cloture_reservations_at' => 'La clôture des réservations doit suivre leur ouverture.',
                ]);
            }

            $jourLivraison = $date->copy()->startOfDay();
            foreach ([
                'ouverture_reservations_at' => $session->ouverture_reservations_at,
                'cloture_reservations_at' => $session->cloture_reservations_at,
            ] as $attribut => $dateHeure) {
                if ($dateHeure && $dateHeure->greaterThanOrEqualTo($jourLivraison)) {
                    throw ValidationException::withMessages([
                        $attribut => 'Les réservations doivent être programmées avant le jour de livraison.',
                    ]);
                }
            }

            if ($session->capacite_max < 1) {
                throw ValidationException::withMessages([
                    'capacite_max' => 'La capacité doit être au moins égale à un panier.',
                ]);
            }
        });
    }

    /** @return HasMany<CompositionPanier, $this> */
    public function compositions(): HasMany
    {
        return $this->hasMany(CompositionPanier::class, 'session_livraison_id');
    }

    /** @return BelongsToMany<ConfigurationPanier, $this> */
    public function configurations(): BelongsToMany
    {
        return $this->belongsToMany(ConfigurationPanier::class, 'configuration_panier_session_livraison')->withTimestamps();
    }

    public function reservationsOuvertes(): bool
    {
        $now = now();

        return $this->date_livraison->greaterThan($now->copy()->startOfDay())
            && $this->statut === self::STATUT_OUVERTE
            && $this->ouverture_reservations_at->lte($now)
            && $this->cloture_reservations_at->gt($now);
    }

    public function statutLabel(): string
    {
        return match ($this->statut) {
            self::STATUT_OUVERTE => 'Ouverte',
            self::STATUT_CLOTUREE => 'Clôturée',
            self::STATUT_LIVREE => 'Livrée',
            default => 'Brouillon',
        };
    }
}
