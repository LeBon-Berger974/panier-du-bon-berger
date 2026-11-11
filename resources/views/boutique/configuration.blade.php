<!DOCTYPE html>
<html lang="fr">
<head>
    @include('partials.head')
    <meta name="description" content="Composition, prix et disponibilité de {{ $configuration->nom }}.">
    <title>{{ $configuration->nom }} - Le Panier du bon berger</title>
</head>
<body class="pbb-public">
@include('boutique.partials.nav')
<main class="pbb-main pbb-config-detail">
    <p><a class="pbb-text-link" href="{{ route('boutique.index') }}">← Retour aux paniers proposés</a></p>
    @if($errors->any())<div class="pbb-alert" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="pbb-config-layout">
        <section class="pbb-config-information">
            <div class="pbb-config-image">
                @if($configuration->photo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($configuration->photo) }}" alt="{{ $configuration->nom }}">
                @else
                    <span aria-hidden="true">🧺</span>
                @endif
            </div>
            <p class="pbb-eyebrow">{{ ucfirst($configuration->sessionLivraison->jour) }} · {{ $configuration->sessionLivraison->date_livraison->translatedFormat('d F Y') }}</p>
            <h1>{{ $configuration->nom }}</h1>
            @if($configuration->description)<p class="pbb-config-description">{{ $configuration->description }}</p>@endif
            <h2>Dans ce panier</h2>
            <ul class="pbb-config-products">
                @forelse($configuration->produits->where('actif', true) as $ligne)
                    @if($ligne->produit)<li><span>{{ $ligne->produit->nom }}</span><strong>{{ rtrim(rtrim(number_format((float) $ligne->quantite, 3, ',', ' '), '0'), ',') }} {{ $ligne->produit->unite }}</strong></li>@endif
                @empty
                    <li>La composition sera renseignée prochainement.</li>
                @endforelse
            </ul>
            <p class="pbb-config-availability">{{ $paniersDisponibles > 0 ? $paniersDisponibles.' panier(s) faisable(s) selon le stock actuel.' : 'Ce panier n’est momentanément pas disponible.' }}</p>
        </section>

        <aside class="pbb-order-summary">
            <h2>Choisir et commander</h2>
            @if($sessions->count() > 1)
                <p class="pbb-muted">Autres dates disponibles pour ce panier :</p>
                <div class="pbb-catalog-meta">
                    @foreach($sessions as $dateSession)
                        @if($dateSession->id !== $configuration->sessionLivraison->id)
                            <a class="pbb-text-link" href="{{ route('boutique.configuration', $configuration) }}?session_livraison_id={{ $dateSession->id }}">{{ ucfirst($dateSession->jour) }} {{ $dateSession->date_livraison->translatedFormat('d F Y') }}</a>
                        @endif
                    @endforeach
                </div>
            @endif
            <p class="pbb-config-delivery">Livraison {{ $configuration->sessionLivraison->jour }} {{ $configuration->sessionLivraison->date_livraison->translatedFormat('d F Y') }}</p>
            @php $reservationDisponible = $configuration->sessionLivraison->reservationsOuvertes() && $paniersDisponibles > 0; @endphp
            <form method="POST" action="{{ route('boutique.commander') }}" id="pbb-config-order-form">
                @csrf
                <input type="hidden" name="configuration_panier_id" value="{{ $configuration->id }}">
                <input type="hidden" name="session_livraison_id" value="{{ $configuration->sessionLivraison->id }}">
                <label class="pbb-quantity-control"><span>Nombre de paniers</span><input id="pbb-quantity" name="nombre_paniers" type="number" min="1" max="{{ min(20, max(1, $paniersDisponibles)) }}" value="1" required @disabled(!$reservationDisponible)></label>
                <dl class="pbb-price-lines">
                    <div><dt>{{ $configuration->nom }}</dt><dd><span id="pbb-baskets">{{ number_format((float) $configuration->prix_ht, 2, ',', ' ') }}</span> € HT</dd></div>
                    <div><dt>Consigne {{ $premiereCommande ? '(première commande)' : '' }}</dt><dd><span id="pbb-deposit">{{ number_format($premiereCommande ? $consigneHt : 0, 2, ',', ' ') }}</span> € HT</dd></div>
                    <div class="pbb-total-line"><dt>Total HT</dt><dd><span id="pbb-total-ht">{{ number_format((float) $configuration->prix_ht + ($premiereCommande ? $consigneHt : 0), 2, ',', ' ') }}</span> €</dd></div>
                    <div><dt>TVA ({{ number_format($tauxTva, 1, ',', ' ') }} %)</dt><dd><span id="pbb-tva">{{ number_format(((float) $configuration->prix_ht + ($premiereCommande ? $consigneHt : 0)) * $tauxTva / 100, 2, ',', ' ') }}</span> €</dd></div>
                    <div class="pbb-total-ttc"><dt>Total TTC</dt><dd><span id="pbb-total-ttc">{{ number_format(((float) $configuration->prix_ht + ($premiereCommande ? $consigneHt : 0)) * (1 + $tauxTva / 100), 2, ',', ' ') }}</span> €</dd></div>
                </dl>

                <fieldset class="pbb-config-pickup">
                    <legend>Lieu de retrait</legend>
                    <label class="pbb-choice-card"><input type="radio" name="mode_retrait" value="ferme" @checked(!$relaisFavoriId)><span class="pbb-choice-copy"><strong>L’exploitation</strong><small>À partir de 10 h</small></span></label>
                    @foreach($relais->where('retrait_ferme', false) as $point)
                        <label class="pbb-choice-card"><input type="radio" name="mode_retrait" value="relais" data-relay-id="{{ $point->id }}" @checked($relaisFavoriId === $point->id)><input type="hidden" name="point_relais_id" value="{{ $point->id }}" @disabled($relaisFavoriId !== $point->id)><span class="pbb-choice-copy"><strong>{{ $point->nom }}</strong><small>{{ $point->ville }}@if($point->creneaux->isNotEmpty()) · {{ $point->creneaux->map(fn($slot) => $slot->resume)->join(', ') }}@endif</small></span></label>
                    @endforeach
                </fieldset>

                @if(!$reservationDisponible)<p class="pbb-muted" role="status">Réservations {{ $paniersDisponibles < 1 ? 'indisponibles faute de stock' : 'fermées pour cette livraison' }}.</p>@endif
                @auth<button class="pbb-button pbb-button-wide" type="submit" @disabled(!$checkoutDisponible || !$reservationDisponible)>Continuer vers le paiement</button>@else<a class="pbb-button pbb-button-wide" href="{{ route('login') }}">Se connecter pour commander</a>@endauth
                @if(!$checkoutDisponible)<p class="pbb-muted">Le paiement en ligne n’est pas encore configuré.</p>@endif
            </form>
        </aside>
    </div>
</main>
<footer class="pbb-footer"><span>© {{ date('Y') }} Le Panier du bon berger</span></footer>
<script>
    const qty = document.getElementById('pbb-quantity');
    const price = {{ Illuminate\Support\Js::from((float) $configuration->prix_ht) }};
    const depositUnit = {{ Illuminate\Support\Js::from((float) $consigneHt) }};
    const vat = {{ Illuminate\Support\Js::from((float) $tauxTva) }};
    const firstOrder = {{ Illuminate\Support\Js::from($premiereCommande) }};
    const money = amount => amount.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    function refreshOrder() {
        if (!qty) return;
        const maxQuantity = Math.max(1, Number(qty.max || 20));
        const count = Math.max(1, Math.min(maxQuantity, parseInt(qty.value || '1', 10)));
        qty.value = count;
        const deposit = firstOrder ? depositUnit * count : 0;
        const baskets = price * count;
        const subtotal = baskets + deposit;
        const tax = subtotal * vat / 100;
        document.getElementById('pbb-baskets').textContent = money(baskets);
        document.getElementById('pbb-deposit').textContent = money(deposit);
        document.getElementById('pbb-total-ht').textContent = money(subtotal);
        document.getElementById('pbb-tva').textContent = money(tax);
        document.getElementById('pbb-total-ttc').textContent = money(subtotal + tax);
    }
    qty?.addEventListener('input', refreshOrder);
    document.querySelectorAll('input[name="mode_retrait"]').forEach(input => input.addEventListener('change', () => {
        document.querySelectorAll('input[name="point_relais_id"]').forEach(point => point.disabled = true);
        if (input.checked && input.dataset.relayId) {
            const point = document.querySelector(`input[name="point_relais_id"][value="${input.dataset.relayId}"]`);
            if (point) point.disabled = false;
        }
    }));
</script>
</body>
</html>
