<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le Panier du Bon Berger</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="min-h-screen bg-amber-50 text-gray-800">

    <header class="flex items-center justify-between bg-white px-8 py-5 shadow-sm">
        <h1 class="text-2xl font-bold text-green-800">
            🧺 Le Panier du Bon Berger
        </h1>

        <nav class="flex items-center gap-4">
            @auth

            <div class="relative">
                <button
                    id="profile-button"
                    type="button"
                    class="flex h-11 w-11 items-center justify-center rounded-full bg-green-700 font-bold text-white transition hover:bg-green-800">
                    {{ auth()->user()->initials() }}
                </button>

                <div
                    id="profile-menu"
                    class="absolute right-0 z-50 mt-3 hidden w-56 rounded-xl border border-gray-100 bg-white p-2 shadow-xl">
                    <div class="border-b px-3 py-3">
                        <p class="font-semibold text-gray-800">
                            {{ auth()->user()->name }}
                        </p>
                        <p class="truncate text-sm text-gray-500">
                            {{ auth()->user()->email }}
                        </p>
                    </div>

                    @if (auth()->user()->role === 'admin')
                    <a href="{{ url('/admin') }}"
                        class="block rounded-lg px-3 py-2 font-medium text-green-800 hover:bg-green-50">
                        Administration
                    </a>
                    @else
                    <a href="{{ url('/dashboard') }}"
                        class="block rounded-lg px-3 py-2 font-medium text-green-800 hover:bg-green-50">
                        Espace client
                    </a>
                    @endif

                    <a href="{{ route('profile.edit') }}"
                        class="block rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                        Mes informations
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full rounded-lg px-3 py-2 text-left text-red-600 hover:bg-red-50">
                            Déconnexion
                        </button>
                    </form>
                </div>
            </div>

            @else
            <a href="{{ route('login') }}"
                class="rounded-lg border border-green-700 px-4 py-2 font-semibold text-green-800 transition hover:bg-green-700 hover:text-white">
                Connexion
            </a>

            <a href="{{ route('register') }}"
                class="rounded-lg bg-green-700 px-4 py-2 font-semibold text-white transition hover:bg-green-800">
                S'inscrire
            </a>
            @endauth
        </nav>
    </header>

    <main class="flex min-h-[75vh] flex-col items-center justify-center px-6 text-center">
        <p class="mb-4 font-semibold uppercase tracking-widest text-green-700">
            Le goût du local et de la fraîcheur
        </p>

        <h2 class="max-w-3xl text-5xl font-bold leading-tight text-green-900">
            Des paniers remplis de bonnes choses !
        </h2>

        <p class="mt-6 max-w-xl text-lg text-gray-600">
            Découvrez nos paniers de produits frais et profitez
            de bons produits sélectionnés avec soin.
        </p>

        <a href="{{ route('register') }}"
            class="mt-8 rounded-xl bg-green-700 px-8 py-4 text-lg font-bold text-white shadow-lg hover:bg-green-800">
            Découvrir nos paniers
        </a>
    </main>

    <footer class="bg-green-900 px-6 py-6 text-center text-white">
        © {{ date('Y') }} Le Panier du Bon Berger — Tous droits réservés.
    </footer>

</body>

<script>
    const button = document.getElementById('profile-button');
    const menu = document.getElementById('profile-menu');

    button?.addEventListener('click', () => {
        menu.classList.toggle('hidden');
    });

    document.addEventListener('click', (event) => {
        if (!button?.contains(event.target) && !menu?.contains(event.target)) {
            menu?.classList.add('hidden');
        }
    });
</script>

</html>