<!DOCTYPE html>
<html lang="fr">
<head>
    @include('partials.head')
    <title>Récapitulatif {{ $commande->reference }} - Le Panier du bon berger</title>
    <style>@media print{.pbb-header,.pbb-footer,.no-print{display:none!important}body{background:#fff!important;color:#172019!important}.pbb-panel{border:1px solid #bbb!important;box-shadow:none!important;color:#172019!important}.pbb-muted{color:#444!important}}</style>
</head>
<body class="pbb-public">
@include('boutique.partials.nav')
<main class="pbb-main pbb-narrow">
    <header class="pbb-page-heading"><p class="pbb-eyebrow">Commande payée</p><h1>Récapitulatif de commande</h1><p>{{ $commande->reference }}</p></header>
    <section class="pbb-panel">
        <p><strong>Client :</strong> {{ $commande->client->name }} ({{ $commande->client->email }})</p>
        <p><strong>Livraison :</strong> {{ ucfirst($commande->sessionLivraison->jour) }} {{ $commande->sessionLivraison->date_livraison->format('d/m/Y') }}</p>
        <p><strong>Retrait :</strong> {{ $commande->pointRelais?->nom ?? 'À la ferme' }}@if($commande->pointRelais) — {{ $commande->pointRelais->adresse }}, {{ $commande->pointRelais->code_postal }} {{ $commande->pointRelais->ville }}@endif</p>
        <p><strong>Nombre de paniers :</strong> {{ $commande->nombre_paniers }}</p>
        <h2>Composition réservée</h2>
        <ul>@foreach($commande->composition ?? [] as $ligne)<li>{{ $ligne['nom'] }} — {{ rtrim(rtrim(number_format((float) $ligne['quantite_par_panier'] * $commande->nombre_paniers, 3, ',', ' '), '0'), ',') }} {{ $ligne['unite'] ?? '' }}</li>@endforeach</ul>
        <hr>
        <p><strong>Paniers :</strong> {{ number_format((float) $commande->montant_paniers, 2, ',', ' ') }} € TTC</p>
        <p><strong>Consigne :</strong> {{ number_format((float) $commande->montant_consigne, 2, ',', ' ') }} € TTC</p>
        <p><strong>Total payé :</strong> {{ number_format((float) $commande->montant_total, 2, ',', ' ') }} € TTC</p>
        <p class="pbb-muted">Récapitulatif de commande. Ce document n’est pas une facture.</p>
    </section>
    <div class="pbb-actions no-print"><button type="button" class="pbb-button" onclick="window.print()">Imprimer ou enregistrer en PDF</button><a class="pbb-button pbb-button-quiet" href="{{ route('boutique.commandes') }}">Retour à mes commandes</a></div>
</main>
</body>
</html>
