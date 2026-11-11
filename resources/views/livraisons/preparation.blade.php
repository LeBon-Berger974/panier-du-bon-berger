<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Préparation — {{ $session->date_livraison->format('d/m/Y') }}</title>
    <style>
        body{font:15px Arial,sans-serif;color:#17231b;margin:24px}h1,h2{color:#14532d}.toolbar{display:flex;justify-content:space-between;margin-bottom:24px}.toolbar button{padding:10px 16px;background:#166534;color:white;border:0;border-radius:8px;font-weight:bold}table{width:100%;border-collapse:collapse;margin:12px 0 24px}th,td{border:1px solid #cbd5ce;padding:8px;text-align:left}th{background:#e9f5e8}.check{width:24px}.muted{color:#536158}.break{break-before:page}@media print{body{margin:0}.toolbar{display:none}a{color:inherit;text-decoration:none}tr{break-inside:avoid}}
    </style>
</head>
<body>
<div class="toolbar"><div><strong>Le Panier du Bon Berger</strong><div class="muted">Feuille de préparation · livraison du {{ $session->date_livraison->format('d/m/Y') }}</div></div><button onclick="window.print()">Imprimer / enregistrer en PDF</button></div>
<h1>Préparation des paniers</h1>
<p>{{ ucfirst($session->jour) }} {{ $session->date_livraison->translatedFormat('d F Y') }} · {{ $commandes->sum('nombre_paniers') }} panier(s)</p>
<h2>Quantités totales à préparer</h2>
<table><thead><tr><th>Produit</th><th>Quantité totale</th></tr></thead><tbody>
@foreach ($totaux as $cle => $quantite)
    @php [$nom, $unite] = explode('|', $cle, 2); @endphp
    <tr><td>{{ $nom }}</td><td>{{ rtrim(rtrim(number_format($quantite, 3, ',', ' '), '0'), ',') }} {{ $unite }}</td></tr>
@endforeach
</tbody></table>
<h2>Commandes et retraits</h2>
<table><thead><tr><th class="check">Fait</th><th>Référence</th><th>Client</th><th>Configuration</th><th>Nombre</th><th>Retrait</th></tr></thead><tbody>
@foreach ($commandes as $commande)
<tr><td>□</td><td>{{ $commande->reference }}</td><td>{{ $commande->client->name }}</td><td>{{ $commande->configuration_panier_nom ?: 'Panier historique' }}</td><td>{{ $commande->nombre_paniers }}</td><td>{{ $commande->mode_retrait === 'ferme' ? 'Ferme' : ($commande->pointRelais?->nom ?? 'Relais') }}</td></tr>
@endforeach
</tbody></table>
</body>
</html>
