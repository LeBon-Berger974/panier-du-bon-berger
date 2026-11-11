<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tournée — {{ $session->date_livraison->format('d/m/Y') }}</title>
    <style>
        body{font:15px Arial,sans-serif;color:#17231b;margin:24px}h1,h2{color:#14532d}.toolbar{display:flex;justify-content:space-between;margin-bottom:24px}.toolbar button{padding:10px 16px;background:#166534;color:white;border:0;border-radius:8px;font-weight:bold}.stop{border:1px solid #cbd5ce;border-radius:12px;padding:16px;margin:12px 0;break-inside:avoid}.stop h2{margin:0 0 6px}.muted{color:#536158}ul{margin-bottom:0}@media print{body{margin:0}.toolbar{display:none}}
    </style>
</head>
<body>
<div class="toolbar"><div><strong>Le Panier du Bon Berger</strong><div class="muted">Tournée de livraison · {{ $session->date_livraison->format('d/m/Y') }}</div></div><button onclick="window.print()">Imprimer / enregistrer en PDF</button></div>
<h1>Tournée de livraison</h1><p>{{ ucfirst($session->jour) }} {{ $session->date_livraison->translatedFormat('d F Y') }} · départ à organiser</p>
@forelse ($groupes as $cle => $commandes)
    @php $ferme = $commandes->first()->mode_retrait === 'ferme'; $point = $commandes->first()->pointRelais; @endphp
    <section class="stop">
        <h2>{{ $ferme ? 'Retrait à la ferme' : $point?->nom }}</h2>
        @if (! $ferme && $point)
            <p>{{ $point->adresse_complete }}@if ($point->contact_telephone) · {{ $point->contact_telephone }}@endif</p>
            @if ($point->latitude && $point->longitude)
                <p><a href="https://maps.google.com/?q={{ $point->latitude }},{{ $point->longitude }}">Ouvrir la carte</a></p>
            @endif
            <p class="muted">Minimum requis : {{ $point->minimum_paniers }} · {{ $commandes->sum('nombre_paniers') }} panier(s) à déposer</p>
        @else
            <p>Retraits à partir de 10 h · {{ $commandes->sum('nombre_paniers') }} panier(s)</p>
        @endif
        <ul>@foreach ($commandes as $commande)<li>{{ $commande->reference }} — {{ $commande->client->name }} — {{ $commande->nombre_paniers }} panier(s)</li>@endforeach</ul>
    </section>
@empty
    <p>Aucune commande payée pour cette livraison.</p>
@endforelse
</body>
</html>
