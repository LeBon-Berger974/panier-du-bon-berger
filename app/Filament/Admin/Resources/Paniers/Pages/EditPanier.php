<?php

namespace App\Filament\Admin\Resources\Paniers\Pages;

use App\Filament\Admin\Resources\Paniers\PanierResource;
use App\Models\Panier;
use App\Models\PanierPreparation;
use App\Models\PanierPreparationReservation;
use App\Models\PanierReservation;
use App\Models\Produit;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

class EditPanier extends EditRecord
{
    protected static string $resource = PanierResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        if (! $record instanceof Panier) {
            throw new \LogicException('Le panier à modifier est indisponible.');
        }

        $data['composition'] = $record
            ->produits()
            ->get()
            ->map(fn ($produit) => [
                'produit_id' => $produit->id,
                'quantite' => $produit->pivot->quantite,
            ])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(
        Model $record,
        array $data
    ): Model {
        $user = auth()->user();

        $canManage = $user?->canUsePermission('baskets.manage') ?? false;

        $canManagePreparedStock =
            Filament::getCurrentPanel()?->getId() === 'admin'
            && $user?->hasRole('admin');

        $allowedData = [];

        if ($canManage) {
            $allowedData = array_intersect_key($data, array_flip([
                'nom',
                'description',
                'photo',
                'prix',
                'actif',
            ]));
        }

        if ($canManagePreparedStock && array_key_exists('stock', $data)) {
            $allowedData['stock'] = $data['stock'];
        }

        // Ne jamais remplacer la composition si elle n'a pas été autorisée.
        $composition = $canManage && array_key_exists('composition', $data)
            ? $data['composition']
            : null;

        if ($allowedData === [] && $composition === null) {
            return $record->refresh();
        }

        return DB::transaction(function () use (
            $record,
            $allowedData,
            $composition
        ): Model {
            $panier = Panier::query()
                ->whereKey($record->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($allowedData !== []) {
                $panier->update($allowedData);
            }

            if ($composition !== null) {
                $produits = [];

                foreach ($composition as $ligne) {
                    if (
                        ! empty($ligne['produit_id'])
                        && isset($ligne['quantite'])
                    ) {
                        $produits[$ligne['produit_id']] = [
                            'quantite' => $ligne['quantite'],
                        ];
                    }
                }

                $panier->produits()->sync($produits);
            }

            return $panier->refresh();
        });
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('preparer')
                ->label('Préparer des paniers')
                ->icon('heroicon-o-archive-box')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->canUsePermission('preparations.create') ?? false)
                ->schema([
                    TextInput::make('nombre')
                        ->label('Nombre de paniers à préparer')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->default(1)
                        ->required(),
                ])
                ->modalHeading('Préparer des paniers')
                ->modalDescription(
                    'Les stocks des produits seront diminués et la préparation sera enregistrée dans l’historique.'
                )
                ->modalSubmitActionLabel('Confirmer la préparation')
                ->action(function (array $data, Panier $record): void {
                    abort_unless(
                        auth()->user()?->canUsePermission('preparations.create'),
                        403
                    );
                    $nombre = (int) $data['nombre'];

                    try {
                        $resultat = DB::transaction(
                            function () use ($record, $nombre): array {
                                $panier = Panier::query()
                                    ->whereKey($record->getKey())
                                    ->lockForUpdate()
                                    ->firstOrFail();

                                $composition = $panier->produits()
                                    ->orderBy('produits.id')
                                    ->get();

                                if ($composition->isEmpty()) {
                                    return [
                                        'succes' => false,
                                        'message' => 'Ajoute au moins un produit à la composition du panier.',
                                    ];
                                }

                                $produits = Produit::query()
                                    ->whereIn(
                                        'id',
                                        $composition->pluck('id')
                                    )
                                    ->orderBy('id')
                                    ->lockForUpdate()
                                    ->get()
                                    ->keyBy('id');

                                $manquants = [];
                                $lignesHistorique = [];

                                foreach ($composition as $ligne) {
                                    $produit = $produits->get(
                                        $ligne->id
                                    );

                                    if (! $produit) {
                                        return [
                                            'succes' => false,
                                            'message' => 'Un produit de la composition est introuvable.',
                                        ];
                                    }

                                    $quantiteParPanier = (float)
                                    $ligne->pivot->quantite;

                                    $quantiteTotale = round(
                                        $quantiteParPanier * $nombre,
                                        2
                                    );

                                    $disponible = (float)
                                    $produit->stock;

                                    if (
                                        $disponible + 0.00001
                                        < $quantiteTotale
                                    ) {
                                        $manquants[] = $produit->nom
                                            .' : disponible '
                                            .$disponible
                                            .', nécessaire '
                                            .$quantiteTotale;
                                    }

                                    $lignesHistorique[] = [
                                        'produit_id' => $produit->id,
                                        'produit_nom' => $produit->nom,
                                        'quantite_par_panier' => $quantiteParPanier,
                                        'quantite_totale' => $quantiteTotale,
                                    ];
                                }

                                if ($manquants !== []) {
                                    return [
                                        'succes' => false,
                                        'message' => 'Stock insuffisant : '
                                            .implode(' ; ', $manquants),
                                    ];
                                }

                                foreach ($lignesHistorique as $ligne) {
                                    $produit = $produits->get(
                                        $ligne['produit_id']
                                    );

                                    $produit->stock = round(
                                        (float) $produit->stock
                                            - $ligne['quantite_totale'],
                                        2
                                    );

                                    $produit->save();
                                }

                                $panier->stock = max(0, (int) $panier->stock + $nombre);

                                $panier->save();

                                $preparation = PanierPreparation::create([
                                    'panier_id' => $panier->id,
                                    'panier_nom' => $panier->nom,
                                    'nombre' => $nombre,
                                    'composition' => $lignesHistorique,
                                ]);

                                // Affecter d'abord la préparation aux réservations
                                // les plus anciennes encore en attente.
                                $resteAPreparer = $nombre;

                                $reservations = PanierReservation::query()
                                    ->where('panier_id', $panier->id)
                                    ->where('statut', 'reserve')
                                    ->orderBy('created_at')
                                    ->orderBy('id')
                                    ->lockForUpdate()
                                    ->get();

                                foreach ($reservations as $reservation) {
                                    if ($resteAPreparer <= 0) {
                                        break;
                                    }

                                    $resteReservation = max(
                                        0,
                                        $reservation->nombre - $reservation->nombre_prepare
                                    );

                                    if ($resteReservation === 0) {
                                        continue;
                                    }

                                    $nombreAffecte = min(
                                        $resteAPreparer,
                                        $resteReservation
                                    );

                                    PanierPreparationReservation::create([
                                        'panier_preparation_id' => $preparation->id,
                                        'panier_reservation_id' => $reservation->id,
                                        'nombre' => $nombreAffecte,
                                    ]);

                                    $reservation->nombre_prepare += $nombreAffecte;

                                    if ($reservation->nombre_prepare >= $reservation->nombre) {
                                        $reservation->statut = 'prepare';
                                    }

                                    $reservation->save();

                                    $resteAPreparer -= $nombreAffecte;
                                }

                                return [
                                    'succes' => true,
                                    'message' => $nombre
                                        .' panier(s) préparé(s). '
                                        .'Les stocks et l’historique ont été mis à jour.',
                                ];
                            }
                        );
                    } catch (Throwable $e) {
                        report($e);

                        Notification::make()
                            ->title('Erreur lors de la préparation')
                            ->body(
                                'Une erreur est survenue. Vérifie les journaux Laravel.'
                            )
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    if (! $resultat['succes']) {
                        Notification::make()
                            ->title('Préparation impossible')
                            ->body($resultat['message'])
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Préparation réussie')
                        ->body($resultat['message'])
                        ->success()
                        ->send();

                    $this->refreshFormData(['stock']);
                }),

        ];

        $preparations = PanierPreparation::query()
            ->where('panier_id', $this->getRecord()->getKey())
            ->whereNull('annule_at')
            ->orderByDesc('created_at')
            ->get();

        foreach ($preparations as $preparation) {
            $actions[] = Action::make(
                'annuler_preparation_'.$preparation->id
            )
                ->label(
                    'Annuler préparation #'
                        .$preparation->id
                        .' ('
                        .$preparation->nombre
                        .' panier(s))'
                )
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn (): bool => auth()->user()?->canUsePermission('preparations.cancel') ?? false)
                ->requiresConfirmation()
                ->modalHeading('Annuler cette préparation ?')
                ->modalDescription(
                    'Les quantités de produits consommées seront restituées. '
                        .'Cette opération ne pourra pas être répétée.'
                )
                ->modalSubmitActionLabel('Confirmer l’annulation')
                ->action(function (Panier $record) use (
                    $preparation
                ): void {
                    abort_unless(
                        auth()->user()?->canUsePermission('preparations.cancel'),
                        403
                    );
                    try {
                        $resultat = DB::transaction(
                            function () use (
                                $record,
                                $preparation
                            ): array {
                                // Verrouiller le panier avant les produits.
                                $panier = Panier::query()
                                    ->whereKey($record->getKey())
                                    ->lockForUpdate()
                                    ->first();

                                $historique = PanierPreparation::query()
                                    ->whereKey($preparation->id)
                                    ->lockForUpdate()
                                    ->first();

                                if (! $historique) {
                                    return [
                                        'succes' => false,
                                        'message' => 'Cette préparation est introuvable.',
                                    ];
                                }

                                if ($historique->annule_at !== null) {
                                    return [
                                        'succes' => false,
                                        'message' => 'Cette préparation a déjà été annulée.',
                                    ];
                                }

                                if (
                                    (int) $historique->panier_id
                                    !== (int) $record->getKey()
                                ) {
                                    return [
                                        'succes' => false,
                                        'message' => 'Cette préparation ne correspond pas à ce panier.',
                                    ];
                                }

                                $composition = $historique->composition;

                                if ($composition === []) {
                                    return [
                                        'succes' => false,
                                        'message' => 'La composition enregistrée est absente ou invalide. Aucun stock n’a été modifié.',
                                    ];
                                }

                                $quantitesArestaurer = [];

                                foreach ($composition as $ligne) {
                                    if (
                                        empty($ligne['produit_id'])
                                        || ! isset(
                                            $ligne['quantite_totale']
                                        )
                                        || ! is_numeric(
                                            $ligne['quantite_totale']
                                        )
                                        || (float) $ligne['quantite_totale'] < 0
                                    ) {
                                        return [
                                            'succes' => false,
                                            'message' => 'La composition historique est invalide. Aucun stock n’a été modifié.',
                                        ];
                                    }

                                    $produitId = (int)
                                    $ligne['produit_id'];

                                    $quantite = (float)
                                    $ligne['quantite_totale'];

                                    $quantitesArestaurer[$produitId] =
                                        ($quantitesArestaurer[$produitId] ?? 0)
                                        + $quantite;
                                }

                                if (
                                    $panier
                                    && (int) $historique->nombre
                                    > (int) $panier->stock
                                ) {
                                    return [
                                        'succes' => false,
                                        'message' => 'Le stock actuel de paniers est inférieur au nombre à annuler. Vérifie les stocks avant de continuer.',
                                    ];
                                }

                                $produits = Produit::query()
                                    ->whereIn(
                                        'id',
                                        array_keys($quantitesArestaurer)
                                    )
                                    ->orderBy('id')
                                    ->lockForUpdate()
                                    ->get()
                                    ->keyBy('id');

                                // Ne rien restaurer partiellement si un
                                // produit historique a été supprimé.
                                foreach ($quantitesArestaurer as $id => $quantite) {
                                    if (! $produits->has($id)) {
                                        return [
                                            'succes' => false,
                                            'message' => 'Le produit #'.$id
                                                .' de cette préparation n’existe plus. '
                                                .'Aucun stock n’a été modifié.',
                                        ];
                                    }
                                }

                                foreach ($quantitesArestaurer as $id => $quantite) {
                                    $produit = $produits->get($id);

                                    $produit->stock = round(
                                        (float) $produit->stock
                                            + $quantite,
                                        2
                                    );

                                    $produit->save();
                                }

                                if ($panier) {
                                    $panier->stock = max(0, (int) $panier->stock - (int) $historique->nombre);

                                    $panier->save();
                                }

                                $historique->annule_at = now();
                                $historique->save();

                                return [
                                    'succes' => true,
                                    'message' => 'La préparation #'
                                        .$historique->id
                                        .' a été annulée. Les stocks ont été restaurés.',
                                ];
                            }
                        );
                    } catch (Throwable $e) {
                        report($e);

                        Notification::make()
                            ->title('Erreur lors de l’annulation')
                            ->body(
                                'La transaction a échoué. Vérifie les journaux Laravel.'
                            )
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    if (! $resultat['succes']) {
                        Notification::make()
                            ->title('Annulation impossible')
                            ->body($resultat['message'])
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Préparation annulée')
                        ->body($resultat['message'])
                        ->success()
                        ->send();

                    $this->refreshFormData(['stock']);
                });
        }

        return $actions;
    }
}
