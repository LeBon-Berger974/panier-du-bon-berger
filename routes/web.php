<?php

use App\Http\Controllers\BoutiqueController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LivraisonDocumentController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->middleware('throttle:10,1')->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->middleware('throttle:10,1')->name('auth.google.callback');

Route::get('/', [BoutiqueController::class, 'index'])->name('home');
Route::get('/paniers', [BoutiqueController::class, 'panier'])->name('boutique.index');
Route::get('/points-relais', [BoutiqueController::class, 'pointsRelais'])->name('relais.index');
Route::post('/demande-point-relais', [BoutiqueController::class, 'demanderPointRelais'])->name('relais.demande');
Route::get('/consigne', [BoutiqueController::class, 'consigne'])->name('consigne');
Route::view('/contact', 'boutique.informations.contact')->name('contact');
Route::view('/conditions-generales', 'boutique.informations.conditions')->name('conditions');
Route::view('/mentions-legales', 'boutique.informations.legal')->name('mentions-legales');
Route::view('/confidentialite', 'boutique.informations.confidentialite')->name('confidentialite');
Route::post('/commandes', [BoutiqueController::class, 'commander'])->middleware('auth')->name('boutique.commander');
Route::get('/mon-compte', [BoutiqueController::class, 'mesCommandes'])->middleware('auth')->name('boutique.commandes');
Route::get('/mon-compte/commande/{reference}/recapitulatif', [BoutiqueController::class, 'recapitulatif'])->middleware('auth')->name('boutique.recapitulatif');
Route::post('/mon-compte/point-relais-favori', [BoutiqueController::class, 'enregistrerRelaisFavori'])->middleware('auth')->name('boutique.relais-favori');
Route::get('/mes-commandes', fn () => redirect()->route('boutique.commandes'))->middleware('auth')->name('boutique.commandes.legacy');
Route::get('/commande/{reference}/confirmation', [BoutiqueController::class, 'confirmation'])->middleware('auth')->name('boutique.confirmation');
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');
Route::middleware('auth')->prefix('admin/sessions-livraison')->name('admin.livraisons.')->group(function (): void {
    Route::get('/{session}/preparation', [LivraisonDocumentController::class, 'preparation'])->name('preparation');
    Route::get('/{session}/tournee', [LivraisonDocumentController::class, 'tournee'])->name('tournee');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
