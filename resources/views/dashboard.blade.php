<x-layouts::app.header>

    <div class="mx-auto w-full max-w-7xl px-6 py-12 lg:px-10">

        <div class="mb-10">
            <h1 class="text-3xl font-bold text-green-900">
                Bienvenue {{ auth()->user()->name }} !
            </h1>

            <p class="mt-2 text-gray-600">
                Retrouvez ici toutes vos informations et réservations.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">

            <a href="#" class="site-card block transition hover:-translate-y-1 hover:shadow-lg">
                <h2 class="site-title">Mes réservations</h2>
                <p class="mt-3 text-gray-600">
                    Consultez toutes vos réservations de paniers.
                </p>
            </a>

            <a href="#" class="site-card block transition hover:-translate-y-1 hover:shadow-lg">
                <h2 class="site-title">Nos paniers</h2>
                <p class="mt-3 text-gray-600">
                    Découvrez tous nos paniers disponibles.
                </p>
            </a>

            <a href="{{ route('profile.edit') }}" class="site-card block transition hover:-translate-y-1 hover:shadow-lg">
                <h2 class="site-title">Mes informations</h2>
                <p class="mt-3 text-gray-600">
                    Modifiez vos informations personnelles.
                </p>
            </a>

        </div>

    </div>

</x-layouts::app.header>
