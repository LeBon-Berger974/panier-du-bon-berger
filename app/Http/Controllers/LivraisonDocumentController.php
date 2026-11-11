<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\SessionLivraison;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LivraisonDocumentController
{
    public function preparation(Request $request, SessionLivraison $session): View
    {
        $this->autoriserAdministration($request);

        $commandes = Commande::query()
            ->with(['client', 'pointRelais'])
            ->where('session_livraison_id', $session->id)
            ->whereIn('statut', [Commande::PAYEE, Commande::PREPAREE, Commande::AU_RELAIS, Commande::RETIREE])
            ->orderBy('point_relais_id')
            ->orderBy('reference')
            ->get();

        $totaux = [];
        foreach ($commandes as $commande) {
            foreach ($commande->composition ?? [] as $ligne) {
                $nom = $ligne['nom'] ?? 'Produit';
                $unite = $ligne['unite'] ?? '';
                $cle = $nom.'|'.$unite;
                $totaux[$cle] = ($totaux[$cle] ?? 0) + ($ligne['quantite_par_panier'] * $commande->nombre_paniers);
            }
        }

        return view('livraisons.preparation', compact('session', 'commandes', 'totaux'));
    }

    public function tournee(Request $request, SessionLivraison $session): View
    {
        $this->autoriserAdministration($request);

        $commandes = Commande::query()
            ->with(['client', 'pointRelais'])
            ->where('session_livraison_id', $session->id)
            ->whereIn('statut', [Commande::PAYEE, Commande::PREPAREE, Commande::AU_RELAIS, Commande::RETIREE])
            ->orderBy('point_relais_id')
            ->orderBy('reference')
            ->get();

        $groupes = $commandes->groupBy(fn (Commande $commande): string => $commande->mode_retrait === 'ferme'
            ? 'ferme'
            : 'relais-'.$commande->point_relais_id);

        return view('livraisons.tournee', compact('session', 'groupes'));
    }

    private function autoriserAdministration(Request $request): void
    {
        abort_unless($request->user()?->hasRole('admin'), 403);
    }
}
