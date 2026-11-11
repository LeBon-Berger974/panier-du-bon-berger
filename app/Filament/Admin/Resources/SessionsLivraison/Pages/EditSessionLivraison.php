<?php

namespace App\Filament\Admin\Resources\SessionsLivraison\Pages;

use App\Filament\Admin\Resources\SessionsLivraison\SessionsLivraisonResource;
use App\Models\SessionLivraison;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use LogicException;

class EditSessionLivraison extends EditRecord
{
    protected static ?string $title = 'Modifier une livraison';

    protected static string $resource = SessionsLivraisonResource::class;

    /** @var array<int, int|string> */
    protected array $configurationsSelectionnees = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['configurations_selectionnees'] = $this->sessionLivraison()->configurations()->pluck('id')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->configurationsSelectionnees = $data['configurations_selectionnees'] ?? [];
        unset($data['configurations_selectionnees']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->sessionLivraison()->configurations()->sync($this->configurationsSelectionnees);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ouvrirReservations')
                ->label('Ouvrir les réservations')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Les clients pourront réserver les configurations activées sur le site pendant la période définie pour cette livraison.')
                ->visible(fn (): bool => $this->sessionLivraison()->statut === SessionLivraison::STATUT_BROUILLON)
                ->action(function (): void {
                    $session = $this->sessionLivraison();

                    $configurations = $session->configurations()->get();

                    if ($configurations->isEmpty() && $session->compositions()->doesntExist()) {
                        throw ValidationException::withMessages([
                            'data.configurations' => 'Préparez au moins une configuration de panier avant d’ouvrir les réservations.',
                        ]);
                    }

                    if (! $session->ouverture_reservations_at || ! $session->cloture_reservations_at || ! $session->cloture_reservations_at->gt(now())) {
                        throw ValidationException::withMessages([
                            'data.cloture_reservations_at' => 'La période de réservation est incomplète ou déjà terminée.',
                        ]);
                    }

                    $session->update(['statut' => SessionLivraison::STATUT_OUVERTE]);
                    $this->refreshFormData(['statut']);
                    Notification::make()->title('Réservations ouvertes')->body('Les paniers activés apparaîtront au catalogue selon leur mode de publication et leurs dates.')->success()->send();
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
                    Notification::make()->title('Réservations clôturées')->success()->send();
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
