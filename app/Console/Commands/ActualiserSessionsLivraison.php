<?php

namespace App\Console\Commands;

use App\Models\SessionLivraison;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ActualiserSessionsLivraison extends Command
{
    protected $signature = 'sessions-livraison:actualiser';

    protected $description = 'Ouvre ou clôture les sessions de livraison selon leurs horaires de réservation.';

    public function handle(): int
    {
        if (! Schema::hasTable('sessions_livraison')) {
            $this->info('La migration des sessions de livraison doit être appliquée.');

            return self::SUCCESS;
        }
        $now = now();

        $cloturees = SessionLivraison::query()
            ->whereIn('statut', [SessionLivraison::STATUT_BROUILLON, SessionLivraison::STATUT_OUVERTE])
            ->where(function ($query) use ($now): void {
                $query->where('cloture_reservations_at', '<=', $now)
                    ->orWhereDate('date_livraison', '<=', $now->toDateString());
            })
            ->update(['statut' => SessionLivraison::STATUT_CLOTUREE]);

        $ouvertes = SessionLivraison::query()
            ->where('statut', SessionLivraison::STATUT_BROUILLON)
            ->where('ouverture_reservations_at', '<=', $now)
            ->where('cloture_reservations_at', '>', $now)
            ->whereHas('compositions')
            ->update(['statut' => SessionLivraison::STATUT_OUVERTE]);

        $this->info("Sessions ouvertes : {$ouvertes}; sessions clôturées : {$cloturees}.");

        return self::SUCCESS;
    }
}
