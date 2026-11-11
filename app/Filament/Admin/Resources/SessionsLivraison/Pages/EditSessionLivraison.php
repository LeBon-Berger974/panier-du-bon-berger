<?php

namespace App\Filament\Admin\Resources\SessionsLivraison\Pages;

use App\Filament\Admin\Resources\SessionsLivraison\SessionsLivraisonResource;
use App\Models\SessionLivraison;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use LogicException;

class EditSessionLivraison extends EditRecord
{
    protected static string $resource = SessionsLivraisonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ouvrirReservations')
                ->label('Ouvrir les réservations')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('La session sera publiée. Les clients pourront réserver pendant la période définie dans ses horaires.')
                ->visible(fn (): bool => $this->sessionLivraison()->statut === SessionLivraison::STATUT_BROUILLON)
                ->action(function (): void {
                    $session = $this->sessionLivraison();

                    if ($session->compositions()->doesntExist()) {
                        throw ValidationException::withMessages([
                            'data.compositions' => 'Ajoutez la composition du panier avant d’ouvrir les réservations.',
                        ]);
                    }

                    if (! $session->ouverture_reservations_at || ! $session->cloture_reservations_at || ! $session->cloture_reservations_at->gt(now())) {
                        throw ValidationException::withMessages([
                            'data.cloture_reservations_at' => 'La période de réservation est incomplète ou déjà terminée.',
                        ]);
                    }

                    $session->update(['statut' => SessionLivraison::STATUT_OUVERTE]);
                    $this->refreshFormData(['statut']);
                }),
            Action::make('cloturerReservations')
                ->label('Clôturer les réservations')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Les réservations de cette session seront arrêtées.')
                ->visible(fn (): bool => $this->sessionLivraison()->statut === SessionLivraison::STATUT_OUVERTE)
                ->action(function (): void {
                    $this->sessionLivraison()->update(['statut' => SessionLivraison::STATUT_CLOTUREE]);
                    $this->refreshFormData(['statut']);
                }),
            Action::make('marquerLivree')
                ->label('Marquer livrée')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->sessionLivraison()->statut === SessionLivraison::STATUT_CLOTUREE)
                ->action(fn () => $this->sessionLivraison()->update([
                    'statut' => SessionLivraison::STATUT_LIVREE,
                ])),
            DeleteAction::make(),
        ];
    }

    private function sessionLivraison(): SessionLivraison
    {
        $record = $this->getRecord();

        if ($record instanceof SessionLivraison) {
            return $record;
        }

        throw new LogicException('La session de livraison est indisponible.');
    }
}
