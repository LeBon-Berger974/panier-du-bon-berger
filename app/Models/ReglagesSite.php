<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReglagesSite extends Model
{
    protected $table = 'reglages_site';

    protected $fillable = [
        'prix_panier_ht', 'consigne_ht', 'taux_tva',
        'ouverture_reservations_mercredi', 'cloture_reservations_mercredi',
        'ouverture_reservations_vendredi', 'cloture_reservations_vendredi',
        'email_contact', 'texte_accueil', 'sujet_email_confirmation', 'texte_email_confirmation',
        'sujet_email_rappel', 'texte_email_rappel',
    ];

    protected function casts(): array
    {
        return [
            'prix_panier_ht' => 'decimal:2',
            'consigne_ht' => 'decimal:2',
            'taux_tva' => 'decimal:2',
        ];
    }

    /** @return array<string, mixed> */
    public static function valeurs(): array
    {
        $reglages = static::query()->firstOrNew();

        return [
            'prix_panier_ht' => (float) ($reglages->prix_panier_ht ?? config('panier.prix_panier_ht_euros', 20)),
            'consigne_ht' => (float) ($reglages->consigne_ht ?? config('panier.consigne_ht_euros', 6)),
            'taux_tva' => (float) ($reglages->taux_tva ?? config('panier.taux_tva_pourcent', 2.1)),
            'ouverture_reservations_mercredi' => $reglages->ouverture_reservations_mercredi,
            'cloture_reservations_mercredi' => $reglages->cloture_reservations_mercredi,
            'ouverture_reservations_vendredi' => $reglages->ouverture_reservations_vendredi,
            'cloture_reservations_vendredi' => $reglages->cloture_reservations_vendredi,
            'email_contact' => $reglages->email_contact,
            'texte_accueil' => $reglages->texte_accueil,
            'sujet_email_confirmation' => $reglages->sujet_email_confirmation,
            'texte_email_confirmation' => $reglages->texte_email_confirmation,
            'sujet_email_rappel' => $reglages->sujet_email_rappel,
            'texte_email_rappel' => $reglages->texte_email_rappel,
        ];
    }
}
