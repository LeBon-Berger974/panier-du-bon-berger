<?php

namespace App\Http\Controllers;

use App\Mail\CommandePayee;
use App\Models\Commande;
use App\Models\Paiement;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class StripeWebhookController
{
    public function __invoke(Request $request): Response
    {
        $secret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        if (! $secret || ! $this->signatureValide($payload, $request->header('Stripe-Signature', ''), $secret)) {
            throw new HttpException(400, 'Signature Stripe invalide.');
        }

        $event = $request->json()->all();
        $type = $event['type'] ?? '';
        if (! in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded', 'checkout.session.expired'], true)) {
            return response('ok');
        }

        $session = $event['data']['object'] ?? [];
        $commandeId = (int) ($session['metadata']['commande_id'] ?? 0);
        if (! $commandeId) {
            throw new HttpException(400, 'Commande absente des métadonnées Stripe.');
        }

        if ($type === 'checkout.session.expired') {
            DB::transaction(function () use ($commandeId, $session): void {
                $commande = Commande::query()->lockForUpdate()->find($commandeId);
                if (! $commande || $commande->statut !== Commande::A_PAYER) {
                    return;
                }
                $commande->update(['statut' => Commande::ANNULEE, 'annulee_at' => now()]);
                $commande->paiements()
                    ->where('reference_prestataire', $session['id'] ?? '')
                    ->where('statut', 'en_attente')
                    ->update(['statut' => 'expire']);
            });

            return response('ok');
        }

        if (($session['payment_status'] ?? null) !== 'paid') {
            return response('ok');
        }

        $resultat = DB::transaction(function () use ($commandeId, $session): string {
            $commande = Commande::query()->lockForUpdate()->findOrFail($commandeId);
            $paiement = $commande->paiements()
                ->where('reference_prestataire', $session['id'] ?? '')
                ->lockForUpdate()
                ->first();

            if (! $paiement || in_array($paiement->statut, ['confirme', 'rembourse'], true)) {
                return 'ignore';
            }
            if ($commande->statut !== Commande::A_PAYER) {
                $paiement->update(['statut' => 'remboursement_requis']);

                return 'remboursement';
            }

            $produits = [];
            foreach ($commande->composition ?? [] as $ligne) {
                $produit = Produit::query()->whereKey($ligne['product_id'])->lockForUpdate()->first();
                $aRetirer = $ligne['quantite_par_panier'] * $commande->nombre_paniers;
                if (! $produit || ! $produit->actif || $aRetirer <= 0 || (float) $produit->stock < $aRetirer) {
                    $paiement->update(['statut' => 'remboursement_requis']);
                    $commande->update(['statut' => Commande::ANNULEE, 'annulee_at' => now()]);

                    return 'remboursement';
                }
                $produits[] = [$produit, $aRetirer];
            }

            foreach ($produits as [$produit, $aRetirer]) {
                $produit->stock = (float) $produit->stock - $aRetirer;
                $produit->save();
            }
            $paiement->update(['statut' => 'confirme', 'confirme_at' => now()]);
            $commande->update(['statut' => Commande::PAYEE, 'payee_at' => now()]);
            if ($commande->montant_consigne > 0) {
                $commande->mouvementsConsignes()->create([
                    'type' => 'pret',
                    'quantite' => $commande->nombre_paniers,
                    'montant_unitaire' => $commande->montant_consigne_ht / $commande->nombre_paniers,
                    'effectue_at' => now(),
                ]);
            }

            return 'payee';
        });

        if ($resultat === 'remboursement') {
            $refund = Http::withToken(config('services.stripe.secret'))
                ->asForm()
                ->withHeaders(['Idempotency-Key' => 'remboursement-commande-'.$commandeId])
                ->post('https://api.stripe.com/v1/refunds', [
                    'payment_intent' => $session['payment_intent'] ?? '',
                    'reason' => 'requested_by_customer',
                    'metadata[commande_id]' => (string) $commandeId,
                ]);
            Paiement::query()
                ->where('commande_id', $commandeId)
                ->where('reference_prestataire', $session['id'] ?? '')
                ->update(['statut' => $refund->successful() ? 'rembourse' : 'remboursement_requis']);
        }

        if ($resultat === 'payee') {
            try {
                $commande = Commande::query()->with(['client', 'sessionLivraison', 'pointRelais'])->findOrFail($commandeId);
                Mail::to($commande->client->email)->send(new CommandePayee($commande));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return response('ok');
    }

    private function signatureValide(string $payload, string $header, string $secret): bool
    {
        if (! preg_match('/(?:^|,)t=(\d+)(?:,|$)/', $header, $timestampMatch)) {
            return false;
        }
        preg_match_all('/(?:^|,)v1=([a-f0-9]+)/', $header, $signatureMatches);
        if ($signatureMatches[1] === []) {
            return false;
        }
        $timestamp = (int) $timestampMatch[1];
        if (abs(time() - $timestamp) > 300) {
            return false;
        }
        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        foreach ($signatureMatches[1] as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
