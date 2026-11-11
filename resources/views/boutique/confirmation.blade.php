<!DOCTYPE html>
<html lang="fr">
<head>@include('partials.head')</head>
<body class="pbb-public">
@include('boutique.partials.nav')
<main class="mx-auto max-w-3xl px-5 py-14">
    <div class="pbb-session p-8 md:p-10">
        <p class="text-sm font-bold uppercase tracking-widest text-[#c9a449]">Commande {{ $commande->reference }}</p>
        @if ($commande->statut === \App\Models\Commande::PAYEE)
            <h1 class="mt-3 text-3xl font-black text-white">Merci, ta réservation est confirmée !</h1><p class="mt-3 text-[#9eaaa2]">Nous préparons {{ $commande->nombre_paniers }} panier(s) pour le {{ $commande->sessionLivraison->date_livraison->translatedFormat('l d F Y') }}.</p>
        @else
            <h1 class="mt-3 text-3xl font-black text-white">Paiement en cours de confirmation</h1><p class="mt-3 text-[#9eaaa2]">La confirmation de la banque peut prendre quelques secondes. Actualise cette page pour voir le statut à jour.</p>
        @endif
        <dl class="mt-8 grid gap-4 border-t border-[#293a30] pt-6 sm:grid-cols-2">
            <div><dt class="text-sm text-[#9eaaa2]">Configuration</dt><dd class="mt-1 font-semibold">{{ $commande->configuration_panier_nom ?: 'Panier' }}</dd></div>
            <div><dt class="text-sm text-[#9eaaa2]">Retrait</dt><dd class="mt-1 font-semibold">{{ $commande->pointRelais?->nom ?? 'À la ferme' }}</dd></div>
            <div><dt class="text-sm text-[#9eaaa2]">Montant</dt><dd class="mt-1 font-semibold">{{ number_format((float) $commande->montant_total, 2, ',', ' ') }} €</dd></div>
            <div><dt class="text-sm text-[#9eaaa2]">Statut</dt><dd class="mt-1 font-semibold">{{ $commande->statut_label }}</dd></div>
            <div><dt class="text-sm text-[#9eaaa2]">Paniers</dt><dd class="mt-1 font-semibold">{{ $commande->nombre_paniers }}</dd></div>
        </dl>
        <a class="site-button mt-8" href="{{ route('home') }}">Retour à la boutique</a>
    </div>
</main>
</body>
</html>
