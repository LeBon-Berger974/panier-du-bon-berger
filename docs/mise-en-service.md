# Mise en service — Le Panier du Bon Berger

## Réglages avant ouverture des commandes

Les montants dans le PDF sont exprimés à 20 € HT par panier et la consigne est estimée à 6 €. Le checkout attend les montants confirmés pour éviter d’encaisser un prix incomplet.

Renseigner dans l’environnement local puis dans l’environnement d’hébergement :

- PANIER_PRIX_TTC_CENTIMES : montant final TTC d’un panier, en centimes.
- PANIER_CONSIGNE_CENTIMES : consigne confirmée par panier, en centimes.
- STRIPE_SECRET : clé secrète Stripe du mode actif.
- STRIPE_WEBHOOK_SECRET : secret de signature du webhook Stripe.
- APP_URL : URL HTTPS du site déployé.
- APP_LOCALE=fr et APP_TIMEZONE=Europe/Paris.
- Variables MAIL_* du fournisseur SMTP ou transactionnel retenu.

Le checkout reste désactivé tant que le prix TTC, la consigne et la clé Stripe ne sont pas configurés.

## Stripe

Créer le webhook sur l’URL publique https://<domaine>/stripe/webhook et y activer les événements :

- checkout.session.completed
- checkout.session.async_payment_succeeded
- checkout.session.expired

Commencer par utiliser des clés et un endpoint du mode test. Tester une commande réussie, une session expirée, les remboursements et la réception du reçu. Remplacer les clés par celles du mode réel seulement après validation du prix affiché et des taxes.

## Tâches planifiées

Le serveur d’hébergement doit appeler php artisan schedule:run chaque minute. Le calendrier ouvre et clôture les réservations automatiquement. Le rappel de livraison est envoyé chaque jour à 10 h, heure de Paris.

## Déploiement

Avant la première migration en production :

1. Sauvegarder la base et les fichiers téléversés.
2. Vérifier l’environnement, l’URL HTTPS, les secrets et les paramètres de courriel.
3. Compiler les ressources avec npm run build.
4. Appliquer les migrations avec php artisan migrate --force.
5. Activer le planificateur Laravel et vérifier les journaux.
6. Tester l’achat en mode test, les confirmations, le retrait, les contenants retournés, le minimum relais et les récompenses.
7. Mettre en place des sauvegardes automatiques et vérifier qu’une restauration fonctionne.

## Informations à intégrer aux pages légales

Les pages CGV et confidentialité ne sont pas publiées avec des coordonnées inventées. Avant l’ouverture publique, fournir la dénomination légale, l’adresse, les coordonnées de contact, les identifiants d’entreprise et de TVA applicables, ainsi que les règles de conservation et de traitement des données.
