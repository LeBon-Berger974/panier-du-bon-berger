<!DOCTYPE html>
<html lang="fr">

<head>@include('partials.head')
    <meta name="description" content="Paniers de légumes de saison et prochaines livraisons du Panier du bon berger.">
    <title>Accueil - Le Panier du bon berger</title>
</head>

<body class="pbb-public">
    @include('boutique.partials.nav')
    <main class="pbb-main">
        <section class="pbb-hero">
            <img class="pbb-hero-background" src="{{ asset('images/panier-recolte-montagnes.png') }}" alt="" aria-hidden="true">
            <img class="pbb-hero-badge" src="{{ asset('images/illustration-reunion.png') }}" alt="Illustration aux couleurs de La Réunion">
        </section>
        <section class="pbb-hero-intro" aria-label="Présentation de la ferme">
            <div class="pbb-hero-copy">
                <p class="pbb-eyebrow">Ferme locale · légumes de saison</p>
                <h1>🧺Le Panier du bon berger </h1>
                <p class="pbb-lead">{{ $texteAccueil }}</p>
                <div class="pbb-actions"><a class="pbb-button" href="{{ route('boutique.index') }}">Voir le panier de la semaine</a><a class="pbb-button pbb-button-quiet" href="{{ route('relais.index') }}">Voir les points relais</a></div>
            </div>
        </section>
        <section class="pbb-weekly-offer" aria-label="Nos paniers">
            <div><p class="pbb-eyebrow">Des paniers selon tes envies</p><p class="pbb-price">Plusieurs compositions</p><p class="pbb-price-note">Photos, contenu, prix et disponibilité avant de commander.</p></div>
            <a class="pbb-text-link" href="{{ route('consigne') }}">La consigne et la FAQ →</a>
        </section>
        <section class="pbb-week">
            <div class="pbb-section-heading">
                <div>
                    <p class="pbb-eyebrow">Prochaines dates</p>
                    <h2>Deux livraisons par semaine</h2>
                </div><a class="pbb-text-link" href="{{ route('boutique.index') }}">Voir les paniers →</a>
            </div>
            <div class="pbb-session-list">@forelse($sessions as $session)@php $remaining=(int)($paniersDisponibles[$session->id]??0); $open=$session->reservationsOuvertes() && $remaining>0; $status=$session->statut === \App\Models\SessionLivraison::STATUT_CLOTUREE || $session->cloture_reservations_at?->lte(now()) ? 'Clôturée' : ($remaining===0 && $session->reservationsOuvertes() ? 'Complet' : ($open ? 'Ouverte' : 'Ouverture prochaine')); @endphp
                <article class="pbb-session pbb-date-row">
                    <div>
                        <p class="pbb-eyebrow">{{ ucfirst($session->jour) }}</p>
                        <h3>Livraison du {{ $session->date_livraison->translatedFormat('d F Y') }}</h3>
                    </div>
                    <div class="pbb-date-status"><span class="pbb-status {{ $open?'pbb-status-open':'' }}">{{ $status }}</span>@if($open)<span class="pbb-muted">{{ $remaining }} panier(s) disponible(s)</span><span class="pbb-muted" data-countdown="{{ $session->cloture_reservations_at->toIso8601String() }}"></span>@endif</div><a class="pbb-text-link" href="{{ route('boutique.index') }}">{{ $open?'Réserver →':'Voir le panier →' }}</a>
                </article>
                @empty<article class="pbb-session pbb-date-row">
                    <div>
                        <p class="pbb-eyebrow">Mercredi et vendredi</p>
                        <h3>Les prochaines dates seront annoncées ici</h3>
                    </div><span class="pbb-muted">Les réservations ouvrent selon le calendrier des livraisons.</span><a class="pbb-text-link" href="{{ route('boutique.index') }}">Voir le panier →</a>
                </article>@endforelse
            </div>
        </section>
        @if($configurations->isNotEmpty())
        <section class="pbb-home-lower">
            <article class="pbb-panel">
                <p class="pbb-eyebrow">Paniers à découvrir</p>
                <h2>Choisis ta composition</h2>
                <div class="pbb-home-configs">
                    @foreach($configurations as $configuration)
                        @php $faisable = (int) ($disponibilitesConfigurations[$configuration->id]['faisable'] ?? 0); @endphp
                        <a class="pbb-home-config" href="{{ route('boutique.configuration', $configuration) }}">
                            @if($configuration->photo)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($configuration->photo) }}" alt="{{ $configuration->nom }}" loading="lazy">@else<span class="pbb-home-config-placeholder">🧺</span>@endif
                            <span><strong>{{ $configuration->nom }}</strong><small>{{ number_format((float) $configuration->prix_ht, 2, ',', ' ') }} € HT · {{ $faisable }} faisable(s)</small></span>
                        </a>
                    @endforeach
                </div>
                <a class="pbb-text-link" href="{{ route('boutique.index') }}">Voir tous les paniers →</a>
            </article>
            <article class="pbb-panel">
                <p class="pbb-eyebrow">Commande en 3 étapes</p>
                <h2>Comment ça marche</h2>
                <ol class="pbb-steps">
                    <li><b>01</b><span>Choisis la livraison et la quantité de paniers.</span></li>
                    <li><b>02</b><span>Choisis le retrait à l’exploitation ou en point relais.</span></li>
                    <li><b>03</b><span>Paye en ligne et récupère ton panier le jour prévu.</span></li>
                </ol>
            </article>
        </section>@endif
        <section class="pbb-home-relay">
            <div>
                <p class="pbb-eyebrow">Retrait près de chez toi</p>
                <h2>Choisis ton lieu de retrait</h2>
                <p class="pbb-muted">Retrait à l’exploitation à partir de 10 h ou dans un point relais disponible.</p>
            </div><a class="pbb-button" href="{{ route('relais.index') }}">Voir les points relais</a>
        </section>
    </main>
    <footer class="pbb-footer"><span>© {{ date('Y') }} Le Panier du bon berger</span><span>Des légumes de saison, cultivés localement.</span><nav aria-label="Informations légales"><a href="{{ route('contact') }}">Contact</a><a href="{{ route('conditions') }}">Conditions générales</a><a href="{{ route('mentions-legales') }}">Mentions légales</a><a href="{{ route('confidentialite') }}">Confidentialité</a></nav></footer>
    <script>
        document.querySelectorAll('[data-countdown]').forEach((node) => {
            const end = new Date(node.dataset.countdown).getTime();
            const update = () => {
                const left = end - Date.now();
                if (left <= 0) {
                    node.textContent = 'Réservations clôturées';
                    return
                }
                const days = Math.floor(left / 86400000),
                    hours = Math.floor(left % 86400000 / 3600000);
                node.textContent = days ? `Clôture dans ${days} j ${hours} h` : `Clôture dans ${hours} h`
            };
            update();
            setInterval(update, 60000)
        });
    </script>
</body>

</html>

