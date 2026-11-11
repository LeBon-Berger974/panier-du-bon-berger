<!DOCTYPE html>
<html lang="fr">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-gray-50 text-gray-800 {{ request()->routeIs('profile.edit', 'security.edit', 'appearance.edit') ? 'pbb-settings-body' : '' }}">
    <header class="flex items-center justify-between bg-white px-8 py-5 shadow-sm">
        <h1 class="text-2xl font-bold text-green-800">🧺 Le Panier du Bon Berger</h1>
        <nav class="flex items-center gap-4">
            <a href="{{ route('home') }}" class="rounded-lg border border-green-700 px-4 py-2 font-semibold text-green-800 transition hover:bg-green-700 hover:text-white">Accueil</a>
            <div class="relative">
                <button id="profile-button" type="button" aria-label="Ouvrir le menu du compte" aria-haspopup="true" aria-expanded="false" class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full bg-green-700 font-bold text-white transition hover:bg-green-800">
                    @if (auth()->user()->avatarUrl())<img src="{{ auth()->user()->avatarUrl() }}" alt="" referrerpolicy="no-referrer" class="h-full w-full object-cover">@else{{ auth()->user()->initials() }}@endif
                </button>
                <div id="profile-menu" class="absolute right-0 z-50 mt-3 hidden w-60 rounded-xl border border-gray-100 bg-white p-2 shadow-xl">
                    <div class="border-b px-3 py-3"><p class="font-semibold text-gray-800">{{ auth()->user()->name }}</p><p class="truncate text-sm text-gray-500">{{ auth()->user()->email }}</p></div>
                    <a href="{{ route('boutique.commandes') }}" class="block rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">Mes commandes</a>
                    <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">Mes informations</a>
                    @if (auth()->user()->hasRole('admin'))<a href="{{ url('/admin') }}" class="block rounded-lg px-3 py-2 font-medium text-green-800 hover:bg-green-50">Administration</a>@endif
                    @if ((auth()->user()->hasRole('preparateur') && auth()->user()->hasAnyPermission()) || auth()->user()->canConsulterCommandesRelais())<a href="{{ url('/prepa') }}" class="block rounded-lg px-3 py-2 font-medium text-green-800 hover:bg-green-50">Espace préparation</a>@endif
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-red-600 hover:bg-red-50">Déconnexion</button></form>
                </div>
            </div>
        </nav>
    </header>
    <script>
        const button = document.getElementById('profile-button');
        const menu = document.getElementById('profile-menu');
        button?.addEventListener('click', () => {
            const isOpen = !menu?.classList.contains('hidden');
            menu?.classList.toggle('hidden');
            button.setAttribute('aria-expanded', String(!isOpen));
        });
        document.addEventListener('click', (event) => {
            if (!button?.contains(event.target) && !menu?.contains(event.target)) {
                menu?.classList.add('hidden');
                button?.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                menu?.classList.add('hidden');
                button?.setAttribute('aria-expanded', 'false');
                button?.focus();
            }
        });
    </script>
    <main class="site-page">{{ $slot }}</main>
    @persist('toast')
        <flux:toast.group><flux:toast /></flux:toast.group>
    @endpersist
    @fluxScripts
</body>
</html>
