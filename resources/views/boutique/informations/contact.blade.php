<!DOCTYPE html>
<html lang="fr">
<head>@include('partials.head')<title>Contact - Le Panier du bon berger</title></head>
<body class="pbb-public">
@include('boutique.partials.nav')
<main class="pbb-main pbb-narrow">
    <header class="pbb-page-heading"><p class="pbb-eyebrow">Nous joindre</p><h1>Contact</h1></header>
    <section class="pbb-panel">
        <h2>Le point de retrait à la ferme</h2>
        <address>3 impasse des Campanules<br>97418 Le Tampon, La Réunion</address>
        <p><a class="pbb-text-link" href="https://www.google.com/maps/search/?api=1&amp;query=3+impasse+des+Campanules+97418+Le+Tampon+La+R%C3%A9union" target="_blank" rel="noopener noreferrer">Voir l’adresse sur Google Maps →</a></p>
        @if(filled($email = \App\Models\ReglagesSite::valeurs()['email_contact']))<p>Courriel : <a class="pbb-text-link" href="mailto:{{ $email }}">{{ $email }}</a></p>@else<p class="pbb-muted">L’adresse courriel de contact sera affichée dès qu’elle sera renseignée dans les réglages du site.</p>@endif
        <p class="pbb-muted">Pour une question sur une commande, indiquez sa référence et la date de livraison.</p>
    </section>
</main>
<footer class="pbb-footer"><span>© {{ date('Y') }} Le Panier du bon berger</span><nav aria-label="Informations légales"><a href="{{ route('conditions') }}">Conditions générales</a><a href="{{ route('mentions-legales') }}">Mentions légales</a><a href="{{ route('confidentialite') }}">Confidentialité</a></nav></footer>
</body>
</html>
