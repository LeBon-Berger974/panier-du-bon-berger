<x-mail::message>
# Merci {{ $commande->client->name }} !

Le paiement de votre commande **{{ $commande->reference }}** est confirmé.

- **Livraison :** {{ $commande->sessionLivraison->date_livraison->translatedFormat('l d F Y') }}
- **Paniers :** {{ $commande->nombre_paniers }}
- **Retrait :** {{ $commande->pointRelais?->adresse_complete ?? 'À la ferme, à partir de 10 h' }}
- **Montant payé :** {{ number_format((float) $commande->montant_total, 2, ',', ' ') }} €

@if ($commande->composition)
### Composition prévue
@foreach ($commande->composition as $ligne)
- {{ $ligne['nom'] }} — {{ $ligne['quantite_par_panier'] }} {{ $ligne['unite'] }} par panier
@endforeach
@endif

@if (filled($texteAdditionnel))
{{ $texteAdditionnel }}
@endif

À bientôt,
**Le Panier du Bon Berger**
</x-mail::message>
