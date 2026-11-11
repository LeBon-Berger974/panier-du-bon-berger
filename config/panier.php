<?php

return [
    // Valeurs de référence du PDF. Elles restent modifiables dans le panneau Réglages.
    'prix_panier_ht_euros' => env('PANIER_PRIX_HT_EUROS', 20),
    'consigne_ht_euros' => env('PANIER_CONSIGNE_HT_EUROS', 6),
    'taux_tva_pourcent' => env('PANIER_TAUX_TVA_POURCENT', 2.1),
    // Valeurs finales requises avant l’ouverture du paiement en ligne.
    'prix_panier_ttc_centimes' => env('PANIER_PRIX_TTC_CENTIMES'),
    'consigne_centimes' => env('PANIER_CONSIGNE_CENTIMES'),
];
