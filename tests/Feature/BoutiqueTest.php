<?php

namespace Tests\Feature;

use App\Mail\CommandePayee;
use App\Models\Commande;
use App\Models\CompositionPanier;
use App\Models\Produit;
use App\Models\SessionLivraison;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BoutiqueTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_boutique_page_is_available_without_open_deliveries(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Le Panier du bon berger')
            ->assertSee('Les prochaines dates seront annoncées ici');
    }

    public function test_public_information_pages_are_available(): void
    {
        foreach ([
            '/contact' => 'Contact',
            '/conditions-generales' => 'Conditions générales',
            '/mentions-legales' => 'Mentions légales',
            '/confidentialite' => 'Confidentialité',
        ] as $path => $heading) {
            $this->get($path)->assertOk()->assertSee($heading);
        }
    }

    public function test_customer_can_open_a_printable_summary_for_their_paid_order(): void
    {
        $client = User::factory()->create();
        $session = SessionLivraison::query()->create([
            'date_livraison' => now()->next(Carbon::FRIDAY)->toDateString(),
            'ouverture_reservations_at' => now()->subHour(),
            'cloture_reservations_at' => now()->addDay(),
            'capacite_max' => 30,
            'statut' => SessionLivraison::STATUT_OUVERTE,
        ]);
        $commande = Commande::query()->create([
            'reference' => 'PBB-REC-001',
            'user_id' => $client->id,
            'session_livraison_id' => $session->id,
            'mode_retrait' => 'ferme',
            'nombre_paniers' => 1,
            'prix_unitaire' => 20.42,
            'montant_paniers' => 20.42,
            'montant_consigne' => 0,
            'montant_total' => 20.42,
            'statut' => Commande::PAYEE,
            'composition' => [['nom' => 'Carottes', 'unite' => 'kg', 'quantite_par_panier' => 1]],
        ]);

        $this->actingAs($client)
            ->get(route('boutique.recapitulatif', $commande->reference))
            ->assertOk()
            ->assertSee('Récapitulatif de commande')
            ->assertSee('Carottes')
            ->assertSee('Ce document n’est pas une facture.');
    }

    public function test_checkout_requires_a_customer_account(): void
    {
        $this->post('/commandes', [
            'session_livraison_id' => 1,
            'nombre_paniers' => 1,
            'mode_retrait' => 'ferme',
        ])->assertRedirect('/login');
    }

    public function test_paid_stripe_checkout_confirms_order_sends_receipt_and_decrements_stock_once(): void
    {
        $secret = 'whsec_feature_test';
        config([
            'services.stripe.secret' => 'sk_test_feature',
            'services.stripe.webhook_secret' => $secret,
            'panier.prix_panier_ttc_centimes' => 2042,
            'panier.consigne_centimes' => 613,
        ]);
        Http::fake([
            'https://api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_checkout',
                'url' => 'https://checkout.stripe.com/c/pay/test',
            ]),
        ]);
        Mail::fake();

        $client = User::factory()->create();
        $product = Produit::query()->create([
            'nom' => 'Carottes',
            'unite' => 'kg',
            'stock' => 100,
            'seuil_alerte' => 1,
            'actif' => true,
        ]);
        $session = SessionLivraison::query()->create([
            'date_livraison' => now()->next(Carbon::FRIDAY)->toDateString(),
            'ouverture_reservations_at' => now()->subHour(),
            'cloture_reservations_at' => now()->addDay(),
            'capacite_max' => 30,
            'statut' => SessionLivraison::STATUT_OUVERTE,
        ]);
        CompositionPanier::query()->create([
            'session_livraison_id' => $session->id,
            'produit_id' => $product->id,
            'quantite' => 0.5,
        ]);

        $checkout = $this->actingAs($client)->post('/commandes', [
            'session_livraison_id' => $session->id,
            'nombre_paniers' => 2,
            'mode_retrait' => 'ferme',
        ]);
        $checkout->assertRedirect('https://checkout.stripe.com/c/pay/test');

        $commande = Commande::query()->firstOrFail();
        $this->assertSame(53.1, (float) $commande->montant_total);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_checkout',
                'payment_status' => 'paid',
                'amount_total' => 5310,
                'currency' => 'eur',
                'payment_intent' => 'pi_test_payment',
                'metadata' => ['commande_id' => (string) $commande->id],
            ]],
        ], JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
        ], $payload)->assertOk();

        $this->assertDatabaseHas('commandes', [
            'id' => $commande->id,
            'statut' => Commande::PAYEE,
        ]);
        $this->assertSame(99.0, (float) $product->fresh()->stock);
        Mail::assertSent(CommandePayee::class, 1);
    }

    public function test_checkout_is_blocked_until_ttc_price_and_deposit_are_confirmed(): void
    {
        config(['services.stripe.secret' => 'sk_test_feature']);
        $client = User::factory()->create();

        $this->actingAs($client)
            ->post('/commandes', [
                'session_livraison_id' => 1,
                'nombre_paniers' => 1,
                'mode_retrait' => 'ferme',
            ])
            ->assertSessionHasErrors('paiement');

        $this->assertDatabaseCount('commandes', 0);
    }

    public function test_stripe_webhook_rejects_an_invalid_signature(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $this->call(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => 't=1,v1=invalid'],
            '{"type":"checkout.session.completed","data":{"object":{}}}',
        )->assertBadRequest();
    }

    public function test_delivery_sessions_open_and_close_automatically_at_the_configured_times(): void
    {
        Carbon::setTestNow('2026-11-09 12:00:00');

        try {
            $opening = SessionLivraison::query()->create([
                'date_livraison' => '2026-11-11',
                'ouverture_reservations_at' => now()->subHour(),
                'cloture_reservations_at' => now()->addHour(),
                'capacite_max' => 30,
                'statut' => SessionLivraison::STATUT_BROUILLON,
            ]);
            $closing = SessionLivraison::query()->create([
                'date_livraison' => '2026-11-13',
                'ouverture_reservations_at' => now()->subDay(),
                'cloture_reservations_at' => now(),
                'capacite_max' => 30,
                'statut' => SessionLivraison::STATUT_OUVERTE,
            ]);
            $product = Produit::query()->create([
                'nom' => 'Produit test',
                'unite' => 'kg',
                'stock' => 10,
                'seuil_alerte' => 1,
                'actif' => true,
            ]);
            foreach ([$opening, $closing] as $session) {
                CompositionPanier::query()->create([
                    'session_livraison_id' => $session->id,
                    'produit_id' => $product->id,
                    'quantite' => 1,
                ]);
            }

            Artisan::call('sessions-livraison:actualiser');

            $this->assertSame(SessionLivraison::STATUT_OUVERTE, $opening->fresh()->statut);
            $this->assertSame(SessionLivraison::STATUT_CLOTUREE, $closing->fresh()->statut);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_delivery_day_sessions_close_even_when_a_legacy_window_runs_later(): void
    {
        Carbon::setTestNow('2026-11-11 13:00:00');

        try {
            $sessionId = DB::table('sessions_livraison')->insertGetId([
                'date_livraison' => '2026-11-11',
                'jour' => 'mercredi',
                'ouverture_reservations_at' => '2026-11-11 10:00:00',
                'cloture_reservations_at' => '2026-11-12 18:00:00',
                'capacite_max' => 200,
                'statut' => SessionLivraison::STATUT_OUVERTE,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $session = SessionLivraison::query()->findOrFail($sessionId);

            $this->assertFalse($session->reservationsOuvertes());

            Artisan::call('sessions-livraison:actualiser');

            $this->assertSame(SessionLivraison::STATUT_CLOTUREE, $session->fresh()->statut);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_reservation_window_cannot_be_scheduled_on_or_after_delivery_day(): void
    {
        try {
            Carbon::setTestNow('2026-11-01 12:00:00');

            $this->expectException(ValidationException::class);

            SessionLivraison::query()->create([
                'date_livraison' => '2026-11-11',
                'ouverture_reservations_at' => '2026-11-11 09:00:00',
                'cloture_reservations_at' => '2026-11-12 18:00:00',
                'capacite_max' => 60,
                'statut' => SessionLivraison::STATUT_BROUILLON,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_expired_checkout_reservations_are_counted_across_all_delivery_sessions(): void
    {
        config([
            'services.stripe.secret' => 'sk_test_feature',
            'panier.prix_panier_ttc_centimes' => 2042,
            'panier.consigne_centimes' => 613,
        ]);
        Http::fake(['https://api.stripe.com/v1/checkout/sessions' => Http::response([
            'id' => 'cs_test_checkout',
            'url' => 'https://checkout.stripe.com/c/pay/test',
        ])]);

        $client = User::factory()->create();
        $product = Produit::query()->create([
            'nom' => 'Courgettes', 'unite' => 'kg', 'stock' => 3,
            'seuil_alerte' => 1, 'actif' => true,
        ]);
        $pendingSession = SessionLivraison::query()->create([
            'date_livraison' => now()->next(Carbon::WEDNESDAY)->toDateString(),
            'ouverture_reservations_at' => now()->subHour(),
            'cloture_reservations_at' => now()->addDay(),
            'capacite_max' => 30,
            'statut' => SessionLivraison::STATUT_OUVERTE,
        ]);
        $checkoutSession = SessionLivraison::query()->create([
            'date_livraison' => now()->next(Carbon::FRIDAY)->toDateString(),
            'ouverture_reservations_at' => now()->subHour(),
            'cloture_reservations_at' => now()->addDay(),
            'capacite_max' => 30,
            'statut' => SessionLivraison::STATUT_OUVERTE,
        ]);
        foreach ([$pendingSession, $checkoutSession] as $session) {
            CompositionPanier::query()->create([
                'session_livraison_id' => $session->id, 'produit_id' => $product->id, 'quantite' => 1,
            ]);
        }
        Commande::query()->create([
            'reference' => 'PBB-PENDING-001', 'user_id' => $client->id,
            'session_livraison_id' => $pendingSession->id, 'mode_retrait' => 'ferme',
            'nombre_paniers' => 2, 'prix_unitaire' => 20.42, 'montant_paniers' => 40.84,
            'montant_consigne' => 12.26, 'montant_total' => 53.10,
            'statut' => Commande::A_PAYER,
            'composition' => [['product_id' => $product->id, 'quantite_par_panier' => 1]],
        ]);

        $this->actingAs($client)->post('/commandes', [
            'session_livraison_id' => $checkoutSession->id,
            'nombre_paniers' => 2,
            'mode_retrait' => 'ferme',
        ])->assertSessionHasErrors('session_livraison_id');

        $this->assertDatabaseCount('commandes', 1);
    }

    public function test_depleted_products_reactivate_when_stock_is_restored(): void
    {
        $product = Produit::query()->create([
            'nom' => 'Tomates', 'unite' => 'kg', 'stock' => 1,
            'seuil_alerte' => 1, 'actif' => true,
        ]);

        $product->update(['stock' => 0]);
        $this->assertFalse($product->fresh()->actif);
        $this->assertTrue($product->fresh()->desactive_stock);

        $product->update(['stock' => 4]);
        $this->assertTrue($product->fresh()->actif);
        $this->assertFalse($product->fresh()->desactive_stock);
    }

    public function test_unpaid_checkout_does_not_count_as_a_first_order_with_no_container_deposit(): void
    {
        config([
            'services.stripe.secret' => 'sk_test_feature',
            'panier.prix_panier_ttc_centimes' => 2042,
            'panier.consigne_centimes' => 613,
        ]);
        Http::fake(['https://api.stripe.com/v1/checkout/sessions' => Http::response([
            'id' => 'cs_test_second_attempt',
            'url' => 'https://checkout.stripe.com/c/pay/test',
        ])]);

        $client = User::factory()->create();
        $product = Produit::query()->create([
            'nom' => 'Salades', 'unite' => 'piece', 'stock' => 20,
            'seuil_alerte' => 1, 'actif' => true,
        ]);
        $session = SessionLivraison::query()->create([
            'date_livraison' => now()->next(Carbon::FRIDAY)->toDateString(),
            'ouverture_reservations_at' => now()->subHour(),
            'cloture_reservations_at' => now()->addDay(),
            'capacite_max' => 30,
            'statut' => SessionLivraison::STATUT_OUVERTE,
        ]);
        CompositionPanier::query()->create([
            'session_livraison_id' => $session->id, 'produit_id' => $product->id, 'quantite' => 1,
        ]);
        Commande::query()->create([
            'reference' => 'PBB-PENDING-002', 'user_id' => $client->id,
            'session_livraison_id' => $session->id, 'mode_retrait' => 'ferme',
            'nombre_paniers' => 1, 'prix_unitaire' => 20.42, 'montant_paniers' => 20.42,
            'montant_consigne' => 6.13, 'montant_total' => 26.55,
            'statut' => Commande::A_PAYER,
            'composition' => [['product_id' => $product->id, 'quantite_par_panier' => 1]],
        ]);

        $this->actingAs($client)->post('/commandes', [
            'session_livraison_id' => $session->id,
            'nombre_paniers' => 1,
            'mode_retrait' => 'ferme',
        ])->assertRedirect('https://checkout.stripe.com/c/pay/test');

        $newOrder = Commande::query()->where('reference', '!=', 'PBB-PENDING-002')->firstOrFail();
        $this->assertSame(6.13, (float) $newOrder->montant_consigne);
        $this->assertSame(26.55, (float) $newOrder->montant_total);
    }
}
