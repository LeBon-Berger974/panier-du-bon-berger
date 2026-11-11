<x-layouts::auth :title="'Confirme ton adresse e-mail'">
    <div class="pbb-verify-card">
        <div class="pbb-verify-icon" aria-hidden="true">✉</div>
        <p class="pbb-verify-eyebrow">Dernière étape</p>
        <h1 class="pbb-verify-title">Confirme ton adresse e-mail</h1>
        <p class="pbb-verify-copy">L’adresse e-mail de ton compte :</p>
        <p class="pbb-verify-email">{{ auth()->user()->email }}</p>
        <p class="pbb-verify-copy">Pour activer ton compte, clique sur le lien de confirmation envoyé par e-mail. Pense à vérifier tes courriers indésirables.</p>

        @if (config('mail.default') === 'log')
            <p class="pbb-verify-warning" role="status">L’envoi des e-mails n’est pas encore configuré. Pour l’instant, les liens sont gardés dans le journal technique et ne sont pas envoyés à cette adresse.</p>
        @elseif (session('status') === 'verification-link-sent')
            <p class="pbb-verify-success" role="status">Un nouveau lien de confirmation vient d’être envoyé.</p>
        @endif

        @if ($errors->has('email'))
            <p class="pbb-verify-error" role="alert">L’e-mail n’a pas pu être envoyé. Vérifie l’adresse et réessaie.</p>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="pbb-button pbb-button-wide">Renvoyer le lien de confirmation</button>
        </form>

        <a href="{{ route('profile.edit') }}" class="pbb-verify-edit">Modifier mon adresse e-mail</a>

        <form method="POST" action="{{ route('logout') }}" class="pbb-verify-logout">
            @csrf
            <button type="submit" data-test="logout-button">Me déconnecter</button>
        </form>
    </div>
</x-layouts::auth>
