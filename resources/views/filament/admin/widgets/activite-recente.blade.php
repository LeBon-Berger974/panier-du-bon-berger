<section class="fi-section rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
    <div class="mb-4 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">En bref</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Commandes, livraisons et derniers stocks · actualisé automatiquement</p>
        </div>
        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ $activities->count() }} activité(s)</span>
    </div>
    @if($activities->isNotEmpty())
        <ol class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach($activities as $activity)
                <li class="rounded-lg border border-gray-100 p-3 dark:border-gray-800">
                    <a href="{{ $activity['url'] }}" class="font-medium text-gray-900 hover:text-emerald-700 dark:text-gray-100 dark:hover:text-emerald-300">{{ $activity['title'] }}</a>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $activity['detail'] }}</p>
                    <time class="mt-2 block text-xs text-gray-500 dark:text-gray-400" datetime="{{ $activity['date']?->toAtomString() }}">{{ $activity['date']?->diffForHumans() }}</time>
                </li>
            @endforeach
        </ol>
    @else
        <p class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">Aucune activité récente à afficher.</p>
    @endif
</section>
