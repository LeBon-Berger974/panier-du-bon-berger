<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\DemandePointRelais;
use App\Models\MouvementConsigne;
use App\Models\Paiement;
use App\Models\PointRelais;
use App\Models\ReglagesSite;
use App\Models\SessionLivraison;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BoutiqueController
{
    public function index(): View
    {
        $sessions = SessionLivraison::query()
            ->with(['compositions.produit'])
            ->whereDate('date_livraison', '>=', today())
            ->where('statut', '!=', SessionLivraison::STATUT_LIVREE)
            ->orderBy('date_livraison')
            ->limit(2)
            ->get();

        ['prix_panier_ht' => $prixPanierHt, 'consigne_ht' => $consigneHt, 'texte_accueil' => $texteAccueil] = ReglagesSite::valeurs();
        $texteAccueil = $texteAccueil ?: 'Un panier composé chaque semaine avec ce qui pousse sur notre exploitation.';

        return view('boutique.index', compact('sessions', 'prixPanierHt', 'consigneHt', 'texteAccueil'));
    }

    public function panier(): View
    {
        $sessions = SessionLivraison::query()
            ->with(['compositions.produit'])
            ->whereDate('date_livraison', '>=', today())
            ->where('statut', '!=', SessionLivraison::STATUT_LIVREE)
            ->orderBy('date_livraison')
            ->limit(2)
            ->get();

        $relais = PointRelais::query()->with('creneaux')->where('actif', true)->orderBy('code_postal')->orderBy('nom')->get();
        ['prix_panier_ht' => $prixPanierHt, 'consigne_ht' => $consigneHt, 'taux_tva' => $tauxTva] = ReglagesSite::valeurs();
        $selectedSessionId = $sessions->first(fn (SessionLivraison $session): bool => $session->reservationsOuvertes())?->id;
        $relaisFavoriId = $relais->contains('id', auth()->user()?->point_relais_favori_id) ? auth()->user()?->point_relais_favori_id : null;
        $prixPanierCentimes = (int) round($prixPanierHt * (1 + $tauxTva / 100) * 100);
        $consigneCentimes = (int) round($consigneHt * (1 + $tauxTva / 100) * 100);
        $premiereCommande = ! auth()->check() || ! Commande::query()
            ->where('user_id', auth()->id())
            ->whereNotIn('statut', [Commande::A_PAYER, Commande::ANNULEE])
            ->exists();
        $checkoutDisponible = filled(config('services.stripe.secret'))
            && is_numeric(config('panier.prix_panier_ttc_centimes'))
            && (int) config('panier.prix_panier_ttc_centimes') === $prixPanierCentimes
            && is_numeric(config('panier.consigne_centimes'))
            && (int) config('panier.consigne_centimes') === $consigneCentimes;

        return view('boutique.paniers', compact('sessions', 'relais', 'prixPanierHt', 'consigneHt', 'tauxTva', 'premiereCommande', 'prixPanierCentimes', 'consigneCentimes', 'checkoutDisponible', 'selectedSessionId', 'relaisFavoriId'));
    }

    public function pointsRelais(): View
    {
        $relais = PointRelais::query()->with('creneaux')->where('actif', true)->orderBy('code_postal')->orderBy('nom')->get();
        $relaisAvecGps = $relais->filter(fn (PointRelais $point): bool => $point->latitude !== null && $point->longitude !== null)->values();

        return view('boutique.relais', compact('relais', 'relaisAvecGps'));
    }

    public function consigne(): View
    {
        ['consigne_ht' => $consigneHt] = ReglagesSite::valeurs();

        return view('boutique.consigne', compact('consigneHt'));
    }

    public function demanderPointRelais(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom_lieu' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'commune' => ['required', 'string', 'max:255'],
            'jour_souhaite' => ['required', 'in:mercredi,vendredi'],
            'nombre_paniers_estime' => ['required', 'integer', 'min:5', 'max:500'],
        ]);

        DemandePointRelais::query()->create($validated);

        return back()->with('demandePointRelaisEnvoyee', 'Votre demande a bien été envoyée.');
    }

    public function commander(Request $request): RedirectResponse
    {
        if (! config('services.stripe.secret')) {
            throw ValidationException::withMessages([
                'paiement' => 'Le paiement en ligne n’est pas encore configuré. Contacte la ferme pour réserver.',
            ]);
        }

        $prixPanierCentimes = config('panier.prix_panier_ttc_centimes');
        $consigneCentimes = config('panier.consigne_centimes');
        $reglages = ReglagesSite::valeurs();
        $prixPanierAttendu = (int) round($reglages['prix_panier_ht'] * (1 + $reglages['taux_tva'] / 100) * 100);
        $consigneAttendue = (int) round($reglages['consigne_ht'] * (1 + $reglages['taux_tva'] / 100) * 100);
        if (! is_numeric($prixPanierCentimes) || (int) $prixPanierCentimes !== $prixPanierAttendu || ! is_numeric($consigneCentimes) || (int) $consigneCentimes !== $consigneAttendue) {
            throw ValidationException::withMessages([
                'paiement' => 'Le prix TTC et le montant de la consigne doivent être confirmés avant l’ouverture du paiement.',
            ]);
        }
        $prixPanierCentimes = (int) $prixPanierCentimes;
        $consigneCentimes = (int) $consigneCentimes;

        $validated = $request->validate([
            'session_livraison_id' => ['required', 'integer', 'exists:sessions_livraison,id'],
            'nombre_paniers' => ['required', 'integer', 'min:1', 'max:20'],
            'mode_retrait' => ['required', 'in:ferme,relais'],
            'point_relais_id' => ['nullable', 'integer', 'exists:points_relais,id'],
        ]);

        if ($validated['mode_retrait'] === 'relais' && empty($validated['point_relais_id'])) {
            throw ValidationException::withMessages([
                'point_relais_id' => 'Choisis un point relais.',
            ]);
        }

        $commande = DB::transaction(function () use ($validated, $request, $prixPanierCentimes, $consigneCentimes, $reglages): Commande {
            $session = SessionLivraison::query()->whereKey($validated['session_livraison_id'])->lockForUpdate()->firstOrFail();
            $request->user()->newQuery()->lockForUpdate()->findOrFail($request->user()->id);

            if (! $session->reservationsOuvertes()) {
                throw ValidationException::withMessages([
                    'session_livraison_id' => 'Les réservations pour cette livraison sont fermées.',
                ]);
            }

            $relais = null;
            if ($validated['mode_retrait'] === 'relais') {
                $relais = PointRelais::query()->where('actif', true)->whereKey($validated['point_relais_id'])->first();
                if (! $relais) {
                    throw ValidationException::withMessages([
                        'point_relais_id' => 'Ce point relais n’est plus disponible.',
                    ]);
                }

            }

            $paniersReserves = Commande::query()
                ->where('session_livraison_id', $session->id)
                ->whereIn('statut', [Commande::A_PAYER, Commande::PAYEE, Commande::PREPAREE, Commande::AU_RELAIS])
                ->where(function ($query): void {
                    $query->where('statut', '!=', Commande::A_PAYER)
                        ->orWhere('created_at', '>=', now()->subMinutes(31));
                })
                ->sum('nombre_paniers');

            if ($paniersReserves + $validated['nombre_paniers'] > $session->capacite_max) {
                throw ValidationException::withMessages([
                    'nombre_paniers' => 'Il ne reste pas assez de paniers pour cette livraison.',
                ]);
            }

            $composition = $session->compositions()
                ->with('produit:id,nom,unite')
                ->get()
                ->map(fn ($item): array => [
                    'product_id' => $item->produit_id,
                    'nom' => $item->produit->nom,
                    'unite' => $item->produit->unite,
                    'quantite_par_panier' => (float) $item->quantite,
                ])
                ->all();

            if ($composition === []) {
                throw ValidationException::withMessages([
                    'session_livraison_id' => 'La composition de ce panier n’est pas encore renseignée.',
                ]);
            }

            foreach ($session->compositions()->with('produit')->orderBy('produit_id')->get() as $ligne) {
                $produit = $ligne->produit?->newQuery()->whereKey($ligne->produit_id)->lockForUpdate()->first();
                $stockEnAttente = Commande::query()
                    ->where('statut', Commande::A_PAYER)
                    ->where('created_at', '>=', now()->subMinutes(31))
                    ->get(['nombre_paniers', 'composition'])
                    ->sum(fn (Commande $commande): float => collect($commande->composition ?? [])
                        ->filter(fn (array $reservation): bool => $reservation['product_id'] === $ligne->produit_id)
                        ->sum(fn (array $reservation): float => $reservation['quantite_par_panier'] * $commande->nombre_paniers));
                $besoin = ($stockEnAttente + (int) $validated['nombre_paniers']) * (float) $ligne->quantite;
                if (! $produit || ! $produit->actif || (float) $produit->stock < $besoin) {
                    throw ValidationException::withMessages([
                        'session_livraison_id' => 'Le stock disponible ne permet plus de réserver cette composition.',
                    ]);
                }
            }

            $aDejaCommande = Commande::query()
                ->where('user_id', $request->user()->id)
                ->whereNotIn('statut', [Commande::A_PAYER, Commande::ANNULEE])
                ->exists();

            $n = (int) $validated['nombre_paniers'];
            $montantPaniers = ($prixPanierCentimes / 100) * $n;
            $montantConsigne = $aDejaCommande ? 0 : ($consigneCentimes / 100) * $n;
            $montantPanierHt = $reglages['prix_panier_ht'] * $n;
            $montantConsigneHt = $aDejaCommande ? 0 : $reglages['consigne_ht'] * $n;
            $montantTva = round(($montantPanierHt + $montantConsigneHt) * $reglages['taux_tva'] / 100, 2);

            $commande = Commande::query()->create([
                'reference' => 'PBB-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'user_id' => $request->user()->id,
                'session_livraison_id' => $session->id,
                'point_relais_id' => $relais?->id,
                'mode_retrait' => $validated['mode_retrait'],
                'nombre_paniers' => $n,
                'prix_unitaire' => $prixPanierCentimes / 100,
                'montant_paniers' => $montantPaniers,
                'montant_consigne' => $montantConsigne,
                'montant_total' => $montantPaniers + $montantConsigne,
                'montant_panier_ht' => $montantPanierHt,
                'montant_consigne_ht' => $montantConsigneHt,
                'montant_tva' => $montantTva,
                'statut' => Commande::A_PAYER,
                'composition' => $composition,
            ]);

            Paiement::query()->create([
                'commande_id' => $commande->id,
                'prestataire' => 'stripe',
                'statut' => 'en_attente',
                'montant_centimes' => (int) round($commande->montant_total * 100),
            ]);

            return $commande;
        });

        $payment = $commande->paiements()->latest()->firstOrFail();
        $stripeResponse = Http::withToken(config('services.stripe.secret'))
            ->asForm()
            ->withHeaders(['Idempotency-Key' => 'commande-'.$commande->reference])
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => route('boutique.confirmation', $commande->reference).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('boutique.index').'?paiement=annule',
                'customer_email' => $request->user()->email,
                'client_reference_id' => $commande->reference,
                'expires_at' => now()->addMinutes(31)->timestamp,
                'metadata[commande_id]' => (string) $commande->id,
                'line_items[0][quantity]' => $commande->nombre_paniers,
                'line_items[0][price_data][currency]' => 'eur',
                'line_items[0][price_data][unit_amount]' => $prixPanierCentimes,
                'line_items[0][price_data][product_data][name]' => 'Panier du Bon Berger',
                'line_items[0][price_data][product_data][description]' => 'Livraison du '.$commande->sessionLivraison->date_livraison->format('d/m/Y'),
                ...($commande->montant_consigne > 0 ? [
                    'line_items[1][quantity]' => 1,
                    'line_items[1][price_data][currency]' => 'eur',
                    'line_items[1][price_data][unit_amount]' => (int) round($commande->montant_consigne * 100),
                    'line_items[1][price_data][product_data][name]' => 'Consigne des paniers',
                ] : []),
            ]);

        if (! $stripeResponse->successful() || ! $stripeResponse->json('url')) {
            $commande->update(['statut' => Commande::ANNULEE, 'annulee_at' => now()]);
            $payment->update(['statut' => 'echoue']);
            report(new \RuntimeException('Stripe Checkout n’a pas pu être créé: '.$stripeResponse->status()));

            throw ValidationException::withMessages([
                'paiement' => 'Le paiement n’a pas pu être préparé. Réessaie dans quelques instants.',
            ]);
        }

        $payment->update([
            'reference_prestataire' => $stripeResponse->json('id'),
            'donnees' => ['checkout_url' => $stripeResponse->json('url')],
        ]);

        return redirect()->away($stripeResponse->json('url'));
    }

    public function mesCommandes(Request $request): View
    {
        $client = $request->user()->load('pointRelaisFavori');
        $relais = PointRelais::query()->where('actif', true)->orderBy('ville')->orderBy('nom')->get();
        $commandes = Commande::query()
            ->with(['sessionLivraison', 'pointRelais'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        $mouvements = MouvementConsigne::query()
            ->whereHas('commande', fn ($query) => $query->where('user_id', $client->id)->whereNotIn('statut', [Commande::A_PAYER, Commande::ANNULEE]))
            ->get();
        $contenantsPretes = (int) $mouvements->whereIn('type', ['pret', 'depot'])->sum('quantite');
        $contenantsRendus = (int) $mouvements->where('type', 'retour')->sum('quantite');
        $contenantsPerdus = (int) $mouvements->where('type', 'perdu')->sum('quantite');
        $contenantsChezClient = max(0, $contenantsPretes - $contenantsRendus - $contenantsPerdus);

        return view('boutique.commandes', compact('client', 'relais', 'commandes', 'contenantsChezClient', 'contenantsPretes', 'contenantsRendus', 'contenantsPerdus'));
    }

    public function enregistrerRelaisFavori(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'point_relais_favori_id' => ['nullable', 'integer', 'exists:points_relais,id'],
        ]);

        if (! empty($validated['point_relais_favori_id']) && ! PointRelais::query()->where('actif', true)->whereKey($validated['point_relais_favori_id'])->exists()) {
            throw ValidationException::withMessages(['point_relais_favori_id' => 'Choisis un point relais disponible.']);
        }

        $request->user()->forceFill(['point_relais_favori_id' => $validated['point_relais_favori_id'] ?? null])->save();

        return back()->with('relaisFavoriEnregistre', 'Ton point relais favori a été enregistré.');
    }

    public function confirmation(Request $request, string $reference): View|RedirectResponse
    {
        $commande = Commande::query()
            ->with(['sessionLivraison', 'pointRelais'])
            ->where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return view('boutique.confirmation', compact('commande'));
    }

    public function recapitulatif(Request $request, string $reference): View
    {
        $commande = Commande::query()
            ->with(['client', 'sessionLivraison', 'pointRelais', 'paiements'])
            ->where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->whereNotIn('statut', [Commande::A_PAYER, Commande::ANNULEE])
            ->firstOrFail();

        return view('boutique.recapitulatif', compact('commande'));
    }
}
