<section class="grid gap-4 xl:grid-cols-2">
    @forelse($sessions as $session)
        @php $reserves=(int)($paniersParSession[$session->id] ?? 0); $ratio=$session->capacite_max > 0 ? min(100, round($reserves / $session->capacite_max * 100)) : 0; @endphp
        <article class="fi-section rounded-xl border p-5">
            <div class="flex flex-wrap items-center justify-between gap-2"><div><p class="text-xs uppercase tracking-wider text-gray-500">{{ ucfirst($session->jour) }} {{ $session->date_livraison->translatedFormat('d F') }}</p><h2 class="mt-1 text-base font-semibold">Livraison {{ $session->jour }}</h2></div><span class="text-xs">{{ $session->statutLabel() }}</span></div>
            <p class="mt-4 text-sm">{{ $reserves }} / {{ $session->capacite_max }} paniers réservés</p>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"><div class="h-full rounded-full bg-amber-400" style="width: {{ $ratio }}%"></div></div>
            <div class="mt-4 grid gap-2 sm:grid-cols-2">@forelse($relaisParSession->get($session->id, collect()) as $groupe)<div class="rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700"><div class="flex justify-between gap-2"><strong>{{ $groupe->pointRelais?->nom ?? 'Point relais' }}</strong><span>{{ $groupe->total }}</span></div>@if($groupe->total < ($groupe->pointRelais?->minimum_paniers ?? 5))<p class="mt-1 text-xs text-amber-500">Seuil relais non atteint</p>@endif</div>@empty<p class="text-xs text-gray-500">Aucune commande en point relais.</p>@endforelse</div>
        </article>
    @empty
        <article class="fi-section rounded-xl border p-5"><h2 class="font-semibold">Prochaines livraisons</h2><p class="mt-2 text-sm text-gray-500">Aucune session de livraison n’a été créée.</p><a class="mt-3 inline-block text-sm text-amber-500" href="{{ route('filament.admin.resources.sessions-livraison.sessions-livraisons.create') }}">Créer une session</a></article>
    @endforelse
</section>
