<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Commande;
use App\Models\SessionLivraison;
use Filament\Widgets\Widget;

class LivraisonsApercuWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.livraisons-apercu';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && (\Filament\Facades\Filament::getCurrentPanel()?->getId() === 'admin'
            || $user->canUsePermission('deliveries.view')
            || $user->canUsePermission('deliveries.manage'));
    }

    protected function getViewData(): array
    {
        $sessions = SessionLivraison::query()
            ->with(['compositions.produit', 'configurations.produits.produit'])
            ->whereDate('date_livraison', '>=', today())
            ->where('statut', '!=', SessionLivraison::STATUT_LIVREE)
            ->orderBy('date_livraison')
            ->limit(2)
            ->get();

        $paniersParSession = Commande::query()
            ->whereIn('session_livraison_id', $sessions->pluck('id'))
            ->whereIn('statut', [Commande::PAYEE, Commande::PREPAREE, Commande::AU_RELAIS, Commande::RETIREE])
            ->selectRaw('session_livraison_id, SUM(nombre_paniers) as total')
            ->groupBy('session_livraison_id')
            ->pluck('total', 'session_livraison_id');

        $relaisParSession = Commande::query()
            ->with('pointRelais')
            ->whereIn('session_livraison_id', $sessions->pluck('id'))
            ->whereNotNull('point_relais_id')
            ->whereIn('statut', [Commande::PAYEE, Commande::PREPAREE, Commande::AU_RELAIS, Commande::RETIREE])
            ->selectRaw('session_livraison_id, point_relais_id, SUM(nombre_paniers) as total')
            ->groupBy('session_livraison_id', 'point_relais_id')
            ->get()
            ->groupBy('session_livraison_id');

        return compact('sessions', 'paniersParSession', 'relaisParSession');
    }
}
