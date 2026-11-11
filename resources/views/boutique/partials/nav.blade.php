<header class="pbb-header"><div class="pbb-nav">
    <a class="pbb-brand" href="{{ route('home') }}"><span aria-hidden="true">🧺</span> Le Panier du bon berger</a>
    <nav aria-label="Navigation principale"><a href="{{ route('home') }}">Accueil</a><a href="{{ route('boutique.index') }}">Le panier</a><a href="{{ route('relais.index') }}">Points relais</a><a href="{{ route('consigne') }}">La consigne</a><a href="{{ route('contact') }}">Contact</a></nav>
    <div class="pbb-nav-actions">
        <details class="pbb-account-menu">
            <summary class="pbb-account-trigger" aria-label="Ouvrir le menu du compte" title="Compte">
                @auth
                    @if (auth()->user()->avatarUrl())<img src="{{ auth()->user()->avatarUrl() }}" alt="" referrerpolicy="no-referrer">@else<span>{{ auth()->user()->initials() }}</span>@endif
                @else
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4.25 4.25 0 1 0 0-8.5 4.25 4.25 0 0 0 0 8.5Zm0 2c-4.15 0-7.5 2.25-7.5 5v1.5h15V19c0-2.75-3.35-5-7.5-5Z"/></svg>
                @endauth
            </summary>
            <div class="pbb-account-dropdown">
                @auth
                    <div class="pbb-account-identity"><strong>{{ auth()->user()->name }}</strong><span>{{ auth()->user()->email }}</span></div>
                    <a href="{{ route('boutique.commandes') }}">Mes commandes</a>
                    <a href="{{ route('profile.edit') }}">Mes informations</a>
                    @if (auth()->user()->hasRole('admin'))<a href="{{ url('/admin') }}">Administration</a>@endif
                    @if ((auth()->user()->hasRole('preparateur') && auth()->user()->hasAnyPermission()) || auth()->user()->canConsulterCommandesRelais())<a href="{{ url('/prepa') }}">Espace préparation</a>@endif
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Déconnexion</button></form>
                @else
                    <a href="{{ route('login') }}">Se connecter</a>
                    <a href="{{ route('register') }}">Créer un compte</a>
                @endauth
            </div>
        </details>
    </div>
</div></header>
<script>
    document.addEventListener('click', (event) => {
        document.querySelectorAll('.pbb-account-menu[open]').forEach((menu) => {
            if (!menu.contains(event.target)) menu.removeAttribute('open');
        });
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') document.querySelectorAll('.pbb-account-menu[open]').forEach((menu) => menu.removeAttribute('open'));
    });
</script>
