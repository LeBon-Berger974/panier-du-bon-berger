<!DOCTYPE html>
<html lang="fr">
<head>@include('partials.head')<title>Confidentialité - Le Panier du bon berger</title></head>
<body class="pbb-public">
@include('boutique.partials.nav')
<main class="pbb-main pbb-narrow">
    <header class="pbb-page-heading"><p class="pbb-eyebrow">Données personnelles</p><h1>Confidentialité</h1></header>
    <section class="pbb-panel">
        <p>Le site utilise les coordonnées de compte et de contact nécessaires à la gestion des commandes, des livraisons, du point relais favori et des contenants consignés. Les données de commande et de paiement servent au suivi de la réservation et à sa confirmation. Les informations de paiement par carte sont traitées par le prestataire de paiement ; le site ne doit pas stocker les numéros complets de carte.</p>
        <h2>À préciser par l’exploitant</h2>
        <p>Le responsable de traitement, les durées de conservation, les destinataires détaillés, la procédure d’exercice des droits et le contact dédié doivent être renseignés avant l’ouverture publique. Le document de projet ne fixe pas ces éléments.</p>
        <p>Pour une demande liée à une commande, consultez la page <a class="pbb-text-link" href="{{ route('contact') }}">Contact</a>.</p>
    </section>
</main>
<footer class="pbb-footer"><span>© {{ date('Y') }} Le Panier du bon berger</span><nav aria-label="Informations légales"><a href="{{ route('contact') }}">Contact</a><a href="{{ route('conditions') }}">Conditions générales</a><a href="{{ route('mentions-legales') }}">Mentions légales</a></nav></footer>
</body>
</html>
