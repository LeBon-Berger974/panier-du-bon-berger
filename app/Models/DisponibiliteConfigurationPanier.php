<?php

namespace App\Models;

use Illuminate\Support\Collection;

class DisponibiliteConfigurationPanier
{
    /**
     * @param  Collection<int, ConfigurationPanier>  $configurations
     * @return array<int, array{stock: int, capacite: int, faisable: int}>
     */
    public static function pour(Collection $configurations, ?SessionLivraison $sessionCible = null): array
    {
        if ($configurations->isEmpty()) {
            return [];
        }

        $configurations->loadMissing(['sessions', 'produits.produit']);
        $commandesEnAttente = Commande::query()
            ->where('statut', Commande::A_PAYER)
            ->where('created_at', '>=', now()->subMinutes(31))
            ->get(['composition', 'nombre_paniers']);

        $stockEnAttente = [];
        foreach ($commandesEnAttente as $commande) {
            foreach ($commande->composition ?? [] as $ligne) {
                $produitId = (int) ($ligne['product_id'] ?? $ligne['produit_id'] ?? 0);
                if ($produitId < 1) {
                    continue;
                }

                $stockEnAttente[$produitId] = ($stockEnAttente[$produitId] ?? 0)
                    + ((float) ($ligne['quantite_par_panier'] ?? 0) * $commande->nombre_paniers);
            }
        }

        $sessions = $sessionCible
            ? collect([$sessionCible])
            : $configurations->flatMap(fn (ConfigurationPanier $configuration) => $configuration->sessions)->unique('id')->values();
        $sessionsIds = $sessions->pluck('id')->all();
        $paniersReserves = Commande::query()
            ->whereIn('session_livraison_id', $sessionsIds)
            ->whereIn('statut', [Commande::A_PAYER, Commande::PAYEE, Commande::PREPAREE, Commande::AU_RELAIS, Commande::RETIREE])
            ->where(function ($query): void {
                $query->where('statut', '!=', Commande::A_PAYER)
                    ->orWhere('created_at', '>=', now()->subMinutes(31));
            })
            ->selectRaw('session_livraison_id, SUM(nombre_paniers) as total')
            ->groupBy('session_livraison_id')
            ->pluck('total', 'session_livraison_id');

        $result = [];
        foreach ($configurations as $configuration) {
            $maximumStock = null;
            foreach ($configuration->produits->where('actif', true) as $ligne) {
                $produit = $ligne->produit;
                $quantite = (float) $ligne->quantite;
                if (! $produit || ! $produit->actif || $quantite <= 0) {
                    $maximumStock = 0;
                    break;
                }

                $stockDisponible = max(0, (float) $produit->stock - ($stockEnAttente[$produit->id] ?? 0));
                $possiblePourProduit = (int) floor(($stockDisponible + 0.000001) / $quantite);
                $maximumStock = $maximumStock === null ? $possiblePourProduit : min($maximumStock, $possiblePourProduit);
            }

            $stock = $maximumStock ?? 0;
            $livraisonsAssociees = $sessionCible ? collect([$sessionCible]) : $configuration->sessions;
            $capacite = $livraisonsAssociees->isEmpty()
                ? $stock
                : (int) $livraisonsAssociees->max(fn (SessionLivraison $session): int => max(
                    0,
                    $session->capacite_max - (int) ($paniersReserves[$session->id] ?? 0),
                ));
            $result[$configuration->id] = [
                'stock' => $stock,
                'capacite' => $capacite,
                'faisable' => min($stock, $capacite),
            ];
        }

        return $result;
    }
}
