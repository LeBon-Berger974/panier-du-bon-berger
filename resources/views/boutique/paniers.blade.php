<!DOCTYPE html>
<html lang="fr">

<head>@include('partials.head')
    <meta name="description" content="Choisissez le panier de la semaine, le créneau de livraison et le lieu de retrait.">
    <title>Le panier de la semaine - Le Panier du bon berger</title>
</head>

<body class="pbb-public">@include('boutique.partials.nav')
    <main class="pbb-main pbb-order-page">
        <header class="pbb-page-heading">
            <p class="pbb-eyebrow">Réservation</p>
            <h1>Le panier de la semaine</h1>
        </header>
        @if($errors->any())<div class="pbb-alert" role="alert"><strong>La réservation n’a pas pu être lancée.</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>@endif
        <form id="pbb-order-form" method="POST" action="{{ route('boutique.commander') }}" class="pbb-order-layout">
            @csrf
            <div class="pbb-order-steps">
                <section class="pbb-order-step">
                    <h2><span>1.</span> Choisissez la livraison</h2>
                    @forelse($sessions as $index=>$session)
                    @php $restants=(int)($paniersDisponibles[$session->id]??0); $ouverte=$session->reservationsOuvertes() && $restants>0; $statut=$session->statut === \App\Models\SessionLivraison::STATUT_CLOTUREE || $session->cloture_reservations_at?->lte(now()) ? 'Clôturée' : ($restants===0 && $session->reservationsOuvertes() ? 'Complet' : ($ouverte ? 'Ouverte' : 'Ouverture prochaine')); @endphp
                    <label class="pbb-choice-card {{ $session->id === $selectedSessionId?'is-selected':'' }} {{ !$ouverte?'is-closed':'' }}">
                        <input type="radio" name="session_livraison_id" value="{{ $session->id }}" data-open="{{ $ouverte?'1':'0' }}" data-available="{{ $restants }}" @checked($session->id === $selectedSessionId) @disabled(!$ouverte) required>
                        <span class="pbb-choice-dot"></span><span class="pbb-choice-copy"><strong>Livraison {{ $session->jour }} {{ $session->date_livraison->translatedFormat('d F') }}</strong><small>Réservation {{ $session->ouverture_reservations_at->translatedFormat('D d/m') }} — {{ $session->cloture_reservations_at->translatedFormat('D d/m') }}@if($ouverte) · {{ $restants }} panier(s) disponible(s)@endif</small></span><span class="pbb-status {{ $ouverte?'pbb-status-open':'' }}">{{ $statut }}</span>
                    </label>
                    @empty<p class="pbb-muted">Aucune livraison à venir n’est ouverte.</p>@endforelse
                </section>
                <section class="pbb-order-step">
                    <h2><span>2.</span> Composition prévue</h2>
                    @foreach($sessions as $session)
                    <div class="pbb-session-composition" data-session="{{ $session->id }}" @if($session->id !== $selectedSessionId) hidden @endif>
                        @forelse($session->compositions as $ligne)<span class="pbb-product-chip">@if($ligne->produit->photo)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($ligne->produit->photo) }}" alt="" loading="lazy">@endif{{ $ligne->produit->nom }} <small>{{ rtrim(rtrim(number_format((float)$ligne->quantite,3,',',' '),'0'),',') }} {{ $ligne->produit->unite }}@if($ligne->produit->saison) · {{ $ligne->produit->saison }}@endif</small></span>@empty<p class="pbb-muted">La composition de ce panier n’est pas encore renseignée.</p>@endforelse
                    </div>
                    @endforeach
                </section>
                <section class="pbb-order-step">
                    <h2><span>3.</span> Lieu de retrait</h2>
                    <label class="pbb-choice-card {{ $relaisFavoriId ? '' : 'is-selected' }}"><input type="radio" name="mode_retrait" value="ferme" @checked(! $relaisFavoriId)><span class="pbb-choice-dot"></span><span class="pbb-choice-copy"><strong>L’exploitation</strong><small>Retrait à partir de 10 h</small></span></label>
                    @foreach($relais->where('retrait_ferme',false) as $point)
                    <label class="pbb-choice-card {{ $relaisFavoriId === $point->id ? 'is-selected' : '' }}"><input type="radio" name="mode_retrait" value="relais" data-relay-id="{{ $point->id }}" @checked($relaisFavoriId===$point->id)><input type="hidden" name="point_relais_id" value="{{ $point->id }}" @disabled($relaisFavoriId !==$point->id)><span class="pbb-choice-dot"></span><span class="pbb-choice-copy"><strong>{{ $point->nom }}</strong><small>{{ $point->ville }}@if($point->creneaux->isNotEmpty()) · {{ $point->creneaux->map(fn($slot) => $slot->resume)->join(', ') }}@endif · minimum {{ $point->minimum_paniers }} paniers</small></span></label>
                    @endforeach
                </section>
            </div>
            <aside class="pbb-order-summary">
                <h2>Votre commande</h2>
                <label class="pbb-quantity-control"><span>Nombre de paniers</span><span><button type="button" data-quantity="minus" aria-label="Retirer un panier">−</button><input id="pbb-quantity" name="nombre_paniers" type="number" value="1" min="1" max="{{ min(20, (int)($paniersDisponibles[$selectedSessionId]??20)) }}" required><button type="button" data-quantity="plus" aria-label="Ajouter un panier">+</button></span></label>
                <dl class="pbb-price-lines">
                    <div>
                        <dt>Panier</dt>
                        <dd><span id="pbb-baskets">{{ number_format($prixPanierHt,2,',',' ') }} €</span></dd>
                    </div>
                    <div>
                        <dt>Consigne du contenant</dt>
                        <dd><span id="pbb-consigne">{{ number_format($premiereCommande?$consigneHt:0,2,',',' ') }} €</span></dd>
                    </div>
                    <div class="pbb-total-line">
                        <dt>Total HT</dt>
                        <dd><span id="pbb-total-ht">{{ number_format($prixPanierHt+($premiereCommande?$consigneHt:0),2,',',' ') }}</span> €</dd>
                    </div>
                    <div>
                        <dt>TVA ({{ number_format($tauxTva,1,',',' ') }} %)</dt>
                        <dd><span id="pbb-tva">{{ number_format(($prixPanierHt+($premiereCommande?$consigneHt:0))*$tauxTva/100,2,',',' ') }}</span> €</dd>
                    </div>
                    <div class="pbb-total-ttc">
                        <dt>Total TTC</dt>
                        <dd><span id="pbb-total-ttc">{{ number_format(($prixPanierHt+($premiereCommande?$consigneHt:0))*(1+$tauxTva/100),2,',',' ') }}</span> €</dd>
                    </div>
                </dl>
                @if(!$reservationDisponible && $sessions->isNotEmpty())<p class="pbb-muted" role="status">Aucune livraison n’est actuellement ouverte aux réservations. Consultez les prochaines dates ci-dessus.</p>@endif
                @auth<button class="pbb-button pbb-button-wide" type="submit" @disabled(!$checkoutDisponible || !$reservationDisponible)>Continuer vers la commande</button>@else<a class="pbb-button pbb-button-wide" href="{{ route('login') }}">Se connecter pour commander</a>@endauth
                @if(!$checkoutDisponible)<p class="pbb-muted">Le paiement en ligne sera disponible lorsque les paramètres de paiement seront configurés.</p>@endif
                <p class="pbb-muted">Le prix du panier est de {{ number_format($prixPanierHt,2,',',' ') }} € HT. La consigne est facturée à la première commande.</p>
            </aside>
        </form>
    </main>
    <footer class="pbb-footer"><span>© {{ date('Y') }} Le Panier du bon berger</span></footer>
    <script>
        const qty = document.getElementById('pbb-quantity'),
            priceHt = {
                {
                    json_encode($prixPanierHt)
                }
            },
            depositHt = {
                {
                    json_encode($consigneHt)
                }
            },
            vat = {
                {
                    json_encode($tauxTva)
                }
            },
            depositFirst = {
                {
                    $premiereCommande ? 'true' : 'false'
                }
            };
        const money = n => n.toLocaleString('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        function refreshOrder() {
            const maxQuantity = Math.max(1, Number(qty.max || 20));
            let n = Math.max(1, Math.min(maxQuantity, parseInt(qty.value || '1', 10)));
            qty.value = n;
            const deposit = depositFirst ? depositHt * n : 0,
                base = priceHt * n,
                total = base + deposit,
                tax = total * vat / 100;
            document.getElementById('pbb-baskets').textContent = money(base) + ' €';
            document.getElementById('pbb-consigne').textContent = money(deposit) + ' €';
            document.getElementById('pbb-total-ht').textContent = money(total);
            document.getElementById('pbb-tva').textContent = money(tax);
            document.getElementById('pbb-total-ttc').textContent = money(total + tax)
        }
        qty.addEventListener('input', refreshOrder);
        document.querySelectorAll('[data-quantity]').forEach(b => b.addEventListener('click', () => {
            qty.value = Number(qty.value) + (b.dataset.quantity === 'plus' ? 1 : -1);
            refreshOrder()
        }));
        document.querySelectorAll('input[name="session_livraison_id"]').forEach(r => r.addEventListener('change', () => {
            document.querySelectorAll('.pbb-session-composition').forEach(c => c.hidden = c.dataset.session !== r.value);
            qty.max = Math.max(1, Math.min(20, Number(r.dataset.available || 1)));
            refreshOrder()
        }));

        function syncRetrait() {
            document.querySelectorAll('input[name="point_relais_id"]').forEach(p => p.disabled = true);
            const selected = document.querySelector('input[name="mode_retrait"]:checked');
            document.querySelectorAll('input[name="mode_retrait"]').forEach(r => r.closest('.pbb-choice-card')?.classList.toggle('is-selected', r.checked));
            if (selected?.dataset.relayId) {
                const p = document.querySelector(`input[name="point_relais_id"][value="${selected.dataset.relayId}"]`);
                if (p) p.disabled = false
            }
        }
        document.querySelectorAll('input[name="mode_retrait"]').forEach(r => r.addEventListener('change', syncRetrait));
        syncRetrait();
    </script>
</body>

</html>
