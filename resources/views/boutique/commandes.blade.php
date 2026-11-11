<!DOCTYPE html>
<html lang="fr">
<head>@include('partials.head')</head>
<body class="pbb-public">
@include('boutique.partials.nav')
<main class="mx-auto max-w-4xl px-5 py-12">
    <p class="pbb-eyebrow">Votre compte en détail</p><h1 class="text-2xl font-bold text-white">Mon compte</h1>
    @if(session('relaisFavoriEnregistre'))<p class="pbb-alert" role="status">{{ session('relaisFavoriEnregistre') }}</p>@endif
    @if($errors->any())<div class="pbb-alert" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <section class="pbb-session"><h2 class="text-sm font-bold text-white">Mon contenant consigné</h2><p class="mt-2 text-sm text-[#9eaaa2]">{{ $contenantsChezClient > 0 ? $contenantsChezClient.' contenant(s) à rapporter' : 'Aucun contenant' }}</p><p class="mt-1 text-xs text-[#9eaaa2]">Prêtés : {{ $contenantsPretes }} · Rendus : {{ $contenantsRendus }} · Perdus : {{ $contenantsPerdus }}</p><p class="mt-2 text-xs text-[#9eaaa2]">La consigne est facturée une seule fois, à votre première commande.</p></section>
        <section class="pbb-session"><h2 class="text-sm font-bold text-white">Point relais favori</h2><p class="mt-2 text-sm text-[#9eaaa2]">{{ $client->pointRelaisFavori?->nom ?? 'Pas encore choisi' }}</p><form class="pbb-form mt-3" method="POST" action="{{ route('boutique.relais-favori') }}">@csrf<label>Choisir un point relais<select name="point_relais_favori_id"><option value="">Pas encore choisi</option>@foreach($relais as $point)<option value="{{ $point->id }}" @selected($client->point_relais_favori_id === $point->id)>{{ $point->nom }} · {{ $point->ville }}</option>@endforeach</select></label><button class="site-button" type="submit">Enregistrer</button></form></section>
    </div>
    <h2 class="mt-7 text-lg font-bold text-white">Mes commandes</h2>
    <div class="mt-4 grid gap-4">
        @forelse ($commandes as $commande)
            <article class="pbb-session">
                <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-extrabold text-white">{{ $commande->reference }}</h2><span class="pbb-status pbb-status-open">{{ $commande->statut_label }}</span></div>
                <p class="mt-2 font-semibold">{{ $commande->configuration_panier_nom ?: 'Panier' }}</p>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-4">
                    <div><dt class="text-[#9eaaa2]">Livraison</dt><dd class="font-semibold">{{ ucfirst($commande->sessionLivraison->jour) }} {{ $commande->sessionLivraison->date_livraison->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-[#9eaaa2]">Retrait</dt><dd class="font-semibold">{{ $commande->pointRelais?->nom ?? 'À la ferme' }}</dd></div>
                    <div><dt class="text-[#9eaaa2]">Paniers</dt><dd class="font-semibold">{{ $commande->nombre_paniers }}</dd></div>
                    <div><dt class="text-[#9eaaa2]">Total</dt><dd class="font-semibold">{{ number_format((float) $commande->montant_total, 2, ',', ' ') }} €</dd></div>
                </dl>
                @if(!in_array($commande->statut, [\App\Models\Commande::A_PAYER, \App\Models\Commande::ANNULEE], true))<p class="mt-4"><a class="pbb-text-link" href="{{ route('boutique.recapitulatif', $commande->reference) }}">Voir le récapitulatif imprimable →</a></p>@endif
            </article>
        @empty
            <div class="pbb-session pbb-empty"><h2 class="text-xl font-bold text-white">Pas encore de commande</h2><p class="pbb-muted">Les prochaines réservations disponibles seront publiées sur la boutique.</p><a class="site-button mt-5" href="{{ route('boutique.index') }}">Voir les paniers</a></div>
        @endforelse
    </div>
    <div class="mt-6">{{ $commandes->links() }}</div>
</main>
</body>
</html>
