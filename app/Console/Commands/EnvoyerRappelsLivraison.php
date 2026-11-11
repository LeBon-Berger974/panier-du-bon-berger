<?php

namespace App\Console\Commands;

use App\Mail\RappelCommande;
use App\Models\Commande;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EnvoyerRappelsLivraison extends Command
{
    protected $signature = 'commandes:envoyer-rappels';

    protected $description = 'Envoie un rappel la veille des retraits de commandes payées';

    public function handle(): int
    {
        $commandes = Commande::query()
            ->with(['client', 'sessionLivraison', 'pointRelais'])
            ->whereHas('sessionLivraison', function ($query): void {
                $query->whereDate('date_livraison', now()->addDay()->toDateString());
            })
            ->whereIn('statut', [Commande::PAYEE, Commande::PREPAREE, Commande::AU_RELAIS])
            ->whereNull('rappel_envoye_at')
            ->get();

        foreach ($commandes as $commande) {

            try {
                Mail::to($commande->client->email)->send(new RappelCommande($commande));
                $commande->update(['rappel_envoye_at' => now()]);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return self::SUCCESS;
    }
}
