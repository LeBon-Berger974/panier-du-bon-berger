<!DOCTYPE html>
<html lang="fr">
<head>
    @include('partials.head')
    <meta name="description" content="Découvrez les paniers proposés par livraison, leur composition, leur prix et les quantités disponibles.">
    <title>Nos paniers - Le Panier du bon berger</title>
</head>
<body class="pbb-public">
@include('boutique.partials.nav')
<main class="pbb-main">
    <header class="pbb-page-heading">
        <p class="pbb-eyebrow">Le catalogue de la ferme</p>
        <h1>Les paniers proposés</h1>
        <p class="pbb-muted">Choisis une composition, découvre son contenu et sélectionne le nombre de paniers avant de commander.</p>
    </header>

    @if($configurations->isNotEmpty())
        <div class="pbb-catalog-grid">
            @foreach($configurations as $configuration)
                @php
                    $disponibilite = $disponibilites[$configuration->id] ?? ['stock' => 0, 'capacite' => 0, 'faisable' => 0];
                    $reservationsOuvertes = $configuration->sessions->contains(fn ($session) => $session->reservationsOuvertes());
                    $achetable = $reservationsOuvertes && $disponibilite['faisable'] > 0;
                @endphp
                <article class="pbb-catalog-card">
                    <a class="pbb-catalog-photo" href="{{ route('boutique.configuration', $configuration) }}" aria-label="Découvrir {{ $configuration->nom }}">
                        @if($configuration->photo)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($configuration->photo) }}" alt="{{ $configuration->nom }}" loading="lazy">
                        @else
                            <span aria-hidden="true">🧺</span>
                        @endif
                    </a>
                    <div class="pbb-catalog-card-body">
                        <div class="pbb-catalog-meta">
                            <span>{{ $configuration->sessions->count() }} date(s) de livraison</span>
                            <span class="pbb-status {{ $achetable ? 'pbb-status-open' : '' }}">{{ $achetable ? 'Disponible' : ($disponibilite['faisable'] < 1 ? 'Épuisé' : 'Réservations à venir') }}</span>
                        </div>
                        <h2>{{ $configuration->nom }}</h2>
                        @if($configuration->description)<p class="pbb-catalog-description">{{ $configuration->description }}</p>@endif
                        <ul class="pbb-catalog-products">
                            @forelse($configuration->produits->where('actif', true) as $ligne)
                                @if($ligne->produit)
                                    <li>{{ $ligne->produit->nom }} <small>{{ rtrim(rtrim(number_format((float) $ligne->quantite, 3, ',', ' '), '0'), ',') }} {{ $ligne->produit->unite }}</small></li>
                                @endif
                            @empty
                                <li>La composition sera renseignée prochainement.</li>
                            @endforelse
                        </ul>
                        <div class="pbb-catalog-footer">
                            <div><strong>{{ number_format((float) $configuration->prix_ht, 2, ',', ' ') }} €</strong><small>HT · {{ $disponibilite['faisable'] }} faisable(s)</small></div>
                            <a class="pbb-button" href="{{ route('boutique.configuration', $configuration) }}">Voir le panier</a>
                        </div>
                        <div class="pbb-catalog-meta" aria-label="Dates de livraison proposées">
                            @foreach($configuration->sessions as $session)
                                <a class="pbb-text-link" href="{{ route('boutique.configuration', $configuration) }}?session_livraison_id={{ $session->id }}">{{ ucfirst($session->jour) }} {{ $session->date_livraison->translatedFormat('d F') }} →</a>
                            @endforeach
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <section class="pbb-catalog-empty">
            <span aria-hidden="true">🧺</span>
            <h2>Les prochains paniers arrivent bientôt</h2>
            <p>Les configurations publiées pour les prochaines livraisons apparaîtront ici avec leurs photos, leur composition et leur prix.</p>
            <a class="pbb-button" href="{{ route('home') }}">Retour à l’accueil</a>
        </section>
    @endif
</main>
<footer class="pbb-footer"><span>© {{ date('Y') }} Le Panier du bon berger</span><span>Les stocks sont mis à jour après la confirmation d’un paiement.</span></footer>
</body>
</html>
