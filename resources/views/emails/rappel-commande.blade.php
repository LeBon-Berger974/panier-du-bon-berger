<x-mail::message>
# Votre panier arrive demain

Bonjour {{ $commande->client->name }},

Petit rappel : votre commande **{{ $commande->reference }}** est prévue le {{ $commande->sessionLivraison->date_livraison->translatedFormat('l d F Y') }}.

- **Paniers :** {{ $commande->nombre_paniers }}
- **Retrait :** {{ $commande->pointRelais?->adresse_complete ?? 'À la ferme, à partir de 10 h' }}

Pensez à rapporter vos contenants consignés si vous en avez.

@if (filled($texteAdditionnel))
{{ $texteAdditionnel }}
@endif

À demain,
**Le Panier du Bon Berger**
</x-mail::message>
