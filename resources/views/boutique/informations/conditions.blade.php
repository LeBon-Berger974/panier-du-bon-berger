<!DOCTYPE html>
<html lang="fr">
<head>@include('partials.head')<title>Conditions générales - Le Panier du bon berger</title></head>
<body class="pbb-public">
@include('boutique.partials.nav')
<main class="pbb-main pbb-narrow">
    <header class="pbb-page-heading"><p class="pbb-eyebrow">Informations de vente</p><h1>Conditions générales</h1></header>
    <section class="pbb-panel">
        <p>Les paniers proposés et leur composition sont présentés pour chaque session de livraison. Le prix indicatif du panier est de 20 € HT. La consigne du contenant réutilisable est indiquée avant la commande ; le montant du document de projet est provisoire et doit être confirmé.</p>
        <p>Les livraisons prévues sont le mercredi et le vendredi. Le retrait peut se faire à la ferme ou dans un point relais proposé pour la session. Le minimum prévu pour une livraison en point relais est de cinq paniers. Les horaires de réservation sont affichés avec chaque session.</p>
        <h2>Éléments à compléter avant les ventes en ligne</h2>
        <p>Le vendeur doit compléter son identité et ses coordonnées, les prix TTC définitifs, les modalités de paiement et de remboursement, les conditions de rétractation applicables aux produits proposés, ainsi que les coordonnées du service client. Ces informations ne sont pas précisées dans le document de projet et ne sont pas inventées ici.</p>
        <p>Pour toute question, consultez la page <a class="pbb-text-link" href="{{ route('contact') }}">Contact</a>.</p>
    </section>
</main>
<footer class="pbb-footer"><span>© {{ date('Y') }} Le Panier du bon berger</span><nav aria-label="Informations légales"><a href="{{ route('contact') }}">Contact</a><a href="{{ route('mentions-legales') }}">Mentions légales</a><a href="{{ route('confidentialite') }}">Confidentialité</a></nav></footer>
</body>
</html>
