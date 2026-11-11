<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Commande;
use App\Models\Produit;
use App\Models\SessionLivraison;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AdminStatsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Vue d’ensemble';

    protected function getStats(): array
    {
        $sessionIds = SessionLivraison::query()
            ->whereDate('date_livraison', '>=', today())
            ->where('statut', '!=', SessionLivraison::STATUT_LIVREE)
            ->pluck('id');

        $commandesActives = Commande::query()
            ->whereIn('session_livraison_id', $sessionIds)
            ->whereIn('statut', [Commande::PAYEE, Commande::PREPAREE, Commande::AU_RELAIS, Commande::RETIREE]);
        $paniersReserves = (clone $commandesActives)->sum('nombre_paniers');

        $montantHt = Commande::query()
            ->whereNotIn('statut', [Commande::ANNULEE, Commande::A_PAYER])
            ->sum('montant_panier_ht');

        $produitsActifs = Produit::query()->where('actif', true)->count();
        $produitsTotal = Produit::query()->count();
        $alertesStock = Produit::query()
            ->where('actif', true)
            ->where('stock', '<=', DB::raw('seuil_alerte'))
            ->count();

        return [
            Stat::make('Paniers réservés', number_format((int) $paniersReserves, 0, ',', ' ')),
            Stat::make('Chiffre d’affaires HT', number_format((float) $montantHt, 2, ',', ' ').' €'),
            Stat::make('Produits actifs', $produitsActifs.' / '.$produitsTotal),
            Stat::make('Alertes stock', (string) $alertesStock)->color($alertesStock > 0 ? 'warning' : 'success'),
        ];
    }
}
