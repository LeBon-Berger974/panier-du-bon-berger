<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Commande;
use App\Models\DisponibiliteConfigurationPanier;
use App\Models\Produit;
use App\Models\SessionLivraison;
use Filament\Widgets\Widget;

class ActiviteRecenteWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.activite-recente';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && (
            $user->canUsePermission('deliveries.view')
            || $user->canUsePermission('deliveries.manage')
            || $user->canUsePermission('relay.orders.view')
            || $user->canUsePermission('baskets.view')
            || $user->canUsePermission('products.view')
            || $user->canUsePermission('preparations.view')
        );
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $events = collect();

        if ($user->canUsePermission('relay.orders.view') || $user->canUsePermission('preparations.view')) {
            $orders = Commande::query()->with(['client', 'sessionLivraison', 'pointRelais'])
                ->when(
                    \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'preparateur' && $user->hasRole('responsable_relais'),
                    fn ($query) => $query->where('mode_retrait', 'relais')->whereIn('point_relais_id', $user->pointsRelaisAffectes()->select('points_relais.id')),
                )
                ->latest()->limit(8)->get();

            foreach ($orders as $order) {
                $events->push([
                    'date' => $order->created_at,
                    'title' => 'Commande '.$order->reference.' · '.($order->configuration_panier_nom ?: 'Panier'),
                    'detail' => ($order->client?->name ?? 'Client').' · '.$order->nombre_paniers.' panier(s) · '.$order->statut_label,
                    'url' => \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'preparateur'
                        ? url('/prepa/commandes') : url('/admin/commandes'),
                ]);
            }
        }

        if ($user->canUsePermission('deliveries.view') || $user->canUsePermission('deliveries.manage')) {
            foreach (SessionLivraison::query()->with('configurations.produits.produit')->latest('updated_at')->limit(5)->get() as $session) {
                $disponibilites = DisponibiliteConfigurationPanier::pour($session->configurations);
                $resumeConfigurations = $session->configurations->map(fn ($configuration): string => $configuration->nom
                    .' : '.($disponibilites[$configuration->id]['faisable'] ?? 0).' faisable(s) · '.$configuration->statutPublication())
                    ->join(' · ');
                $events->push([
                    'date' => $session->updated_at,
                    'title' => 'Livraison du '.$session->date_livraison->format('d/m/Y'),
                    'detail' => ($resumeConfigurations ?: 'Aucune configuration').' · '.$session->statutLabel(),
                    'url' => \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'preparateur'
                        ? url('/prepa/sessions-livraison/sessions-livraisons') : url('/admin/sessions-livraison/sessions-livraisons'),
                ]);
            }
        }

        if ($user->canUsePermission('products.view') || $user->canUsePermission('products.manage')) {
            foreach (Produit::query()->latest('updated_at')->limit(5)->get() as $product) {
                $events->push([
                    'date' => $product->updated_at,
                    'title' => 'Stock · '.$product->nom,
                    'detail' => 'Stock actuel : '.rtrim(rtrim(number_format((float) $product->stock, 3, ',', ' '), '0'), ',').' '.($product->unite ?? ''),
                    'url' => \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'preparateur'
                        ? url('/prepa/produits') : url('/admin/produits'),
                ]);
            }
        }

        $activities = $events->sortByDesc('date')->take(12)->values();

        return compact('activities');
    }
}
