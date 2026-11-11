<!DOCTYPE html>
<html lang="fr">
<head>@include('partials.head')<title>Mentions légales - Le Panier du bon berger</title></head>
<body class="pbb-public">
@include('boutique.partials.nav')
<main class="pbb-main pbb-narrow">
    <header class="pbb-page-heading"><p class="pbb-eyebrow">Informations du site</p><h1>Mentions légales</h1></header>
    <section class="pbb-panel">
        <h2>Éditeur</h2><p>Le Panier du bon berger — identité juridique, forme de l’entreprise, adresse du siège, numéro SIRET et nom du directeur de publication à renseigner par l’exploitant.</p>
        <h2>Hébergement</h2><p>Nom et coordonnées de l’hébergeur à renseigner avant la mise en production.</p>
        <p class="pbb-muted">Ces éléments n’apparaissent pas dans le document de projet. Ils doivent être complétés avec les informations réelles de l’entreprise et de l’hébergeur.</p>
    </section>
</main>
<footer class="pbb-footer"><span>© {{ date('Y') }} Le Panier du bon berger</span><nav aria-label="Informations légales"><a href="{{ route('contact') }}">Contact</a><a href="{{ route('conditions') }}">Conditions générales</a><a href="{{ route('confidentialite') }}">Confidentialité</a></nav></footer>
</body>
</html>
