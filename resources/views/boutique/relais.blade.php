<!DOCTYPE html>
<html lang="fr">

<head>@include('partials.head')<title>Points relais - Le Panier du bon berger</title>
</head>

<body class="pbb-public">@include('boutique.partials.nav')
    <main class="pbb-main">
        <header class="pbb-page-heading">
            <p class="pbb-eyebrow">Retrait des paniers</p>
            <h1>Où récupérer votre panier ?</h1>
            <p class="pbb-lead">Choisissez votre lieu de retrait parmi les lieux disponibles.</p>
        </header>
        <div class="pbb-relay-cards">@forelse($relais as $point)<article class="pbb-panel">
                <p class="pbb-eyebrow">{{ $point->retrait_ferme ? 'Exploitation' : 'Point relais' }}</p>
                <h2>{{ $point->nom }}</h2>
                <p>{{ $point->adresse_complete }}</p>@if($point->contact_nom)<p class="pbb-muted">Contact : {{ $point->contact_nom }}@if($point->contact_telephone) · {{ $point->contact_telephone }}@endif</p>@endif@if($point->creneaux->isNotEmpty())<p class="pbb-muted">{{ $point->creneaux->map(fn($slot) => $slot->resume)->join(' · ') }}</p>@endif@if(!$point->retrait_ferme)<p class="pbb-muted">Minimum {{ $point->minimum_paniers }} paniers par livraison.</p>@endif<a class="pbb-text-link" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($point->adresse_complete) }}" target="_blank" rel="noopener">Voir sur Google Maps →</a>
            </article>@empty<p class="pbb-session pbb-muted">Aucun point relais disponible.</p>@endforelse</div>
        <section class="pbb-map-section" aria-label="Carte des points relais">
            <h2>Carte des points relais</h2>@forelse($relais as $point)<article class="pbb-map-location">
                <h3>{{ $point->nom }}</h3><iframe title="{{ $point->nom }} sur Google Maps" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps?q={{ urlencode($point->adresse_complete) }}&output=embed"></iframe><a class="pbb-text-link" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($point->adresse_complete) }}" target="_blank" rel="noopener">Ouvrir dans Google Maps →</a>
            </article>@empty<div class="pbb-map-empty"><strong>Carte des points relais</strong><span>Aucun point relais disponible.</span></div>@endforelse
        </section>
        <section class="pbb-relay-request" id="devenir-point-relais">
            <div>
                <p class="pbb-eyebrow">Devenez point relais</p>
                <h2>Vous souhaitez accueillir un point relais ?</h2>
                <p>Indiquez votre lieu, vos coordonnées et le jour de livraison souhaité.</p>
                <p class="pbb-muted">Un minimum de 5 paniers est requis par livraison.</p>
            </div>
            <form class="pbb-form" method="POST" action="{{ route('relais.demande') }}">@csrf
                @if(session('demandePointRelaisEnvoyee'))<p class="pbb-alert" role="status">{{ session('demandePointRelaisEnvoyee') }}</p>@endif
                @if($errors->any())<div class="pbb-alert" role="alert">
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>@endif
                <label>Nom du lieu ou du groupe<input name="nom_lieu" value="{{ old('nom_lieu') }}" required maxlength="255"></label><label>Votre nom et téléphone<input name="contact" value="{{ old('contact') }}" required maxlength="255"></label><label>Commune<input name="commune" value="{{ old('commune') }}" required maxlength="255"></label><label>Jour souhaité<select name="jour_souhaite" required>
                        <option value="">Choisir</option>
                        <option value="mercredi" @selected(old('jour_souhaite')==='mercredi' )>Mercredi</option>
                        <option value="vendredi" @selected(old('jour_souhaite')==='vendredi' )>Vendredi</option>
                    </select></label><label>Nombre de paniers estimé<input name="nombre_paniers_estime" type="number" min="5" max="500" value="{{ old('nombre_paniers_estime', 5) }}" required></label><button class="site-button" type="submit">Envoyer ma demande</button>
            </form>
        </section>
    </main>
    <footer class="pbb-footer"><span>© {{ date('Y') }} Le Panier du bon berger</span></footer>
</body>

</html>