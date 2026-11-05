<div class="flex items-start gap-8 max-md:flex-col">

    {{-- MENU LATÉRAL --}}
    <aside class="w-full shrink-0 rounded-2xl border border-green-100 bg-white p-4 shadow-sm md:w-[240px]">

        <h3 class="mb-4 px-3 text-sm font-bold uppercase tracking-wider text-green-800">
            Mon compte
        </h3>

        <nav class="space-y-2">

            <a href="{{ route('profile.edit') }}"
                wire:navigate
                class="flex items-center rounded-xl px-4 py-3 font-medium transition
               {{ request()->routeIs('profile.edit') ? 'bg-green-800 text-white shadow-sm' : 'text-green-900 hover:bg-green-50' }}">
                <span class="mr-3">👤</span>
                Mon profil
            </a>

            <a href="{{ route('security.edit') }}"
                wire:navigate
                class="flex items-center rounded-xl px-4 py-3 font-medium transition
               {{ request()->routeIs('security.edit') ? 'bg-green-800 text-white shadow-sm' : 'text-green-900 hover:bg-green-50' }}">
                <span class="mr-3">🔒</span>
                Sécurité
            </a>


        </nav>
    </aside>

    {{-- CONTENU --}}
    <div class="min-w-0 flex-1 self-stretch max-md:w-full">

        <div class="rounded-2xl border border-green-100 bg-white p-6 shadow-sm">

            <flux:heading class="!text-green-900">
                {{ $heading ?? '' }}
            </flux:heading>

            <flux:subheading class="!text-gray-600">
                {{ $subheading ?? '' }}
            </flux:subheading>

            <div class="mt-6 w-full max-w-lg">
                {{ $slot }}
            </div>

        </div>

    </div>

</div>