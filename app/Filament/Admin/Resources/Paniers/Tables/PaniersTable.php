<?php

namespace App\Filament\Admin\Resources\Paniers\Tables;

use App\Models\Panier;
use App\Models\PanierReservation;
use App\Models\Produit;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaniersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')
                    ->label('Photo')
                    ->disk('public')
                    ->imageHeight(70)
                    ->imageWidth(100)
                    ->checkFileExistence(false),

                TextColumn::make('nom')
                    ->label('Nom du panier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('prix')
                    ->label('Prix')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('paniers_faisables')
                    ->label('Réalisables avec les ingrédients')
                    ->state(fn (Panier $record): int => self::calculerPaniersFaisables($record))
                    ->sortable(false),

                TextColumn::make('paniers_reserves')
                    ->label('Réservations en attente')
                    ->state(fn (Panier $record): int => (int) PanierReservation::query()
                        ->where('panier_id', $record->id)
                        ->where('statut', 'reserve')
                        ->sum(DB::raw('nombre - nombre_prepare'))),

                ToggleColumn::make('actif')
                    ->label('Réservable')
                    ->onColor('success')
                    ->offColor('danger')
                    ->disabled(fn (Panier $record): bool => $record->produits()->count() === 0)
                    ->visible(fn (): bool => auth()->user()?->canUsePermission('baskets.manage') ?? false)
                    ->beforeStateUpdated(function (): void {
                        abort_unless(
                            auth()->user()?->canUsePermission('baskets.manage'),
                            403
                        );
                    }),

                TextColumn::make('stock')
                    ->label('Paniers préparés')
                    ->numeric()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Modifier')
                    ->visible(
                        fn (): bool => auth()->user()?->canUsePermission('baskets.manage')
                            || auth()->user()?->canUsePermission('preparations.view')
                            || auth()->user()?->canUsePermission('preparations.create')
                            || auth()->user()?->canUsePermission('preparations.cancel')
                    ),
                Action::make('reserver')
                    ->label('Ajouter une réservation')
                    ->icon('heroicon-o-calendar')
                    ->color('success')
                    ->visible(fn (): bool => auth()->user()?->canUsePermission('reservations.create') ?? false)
                    ->schema([
                        TextInput::make('nombre')
                            ->label('Nombre de paniers à réserver')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                    ])
                    ->action(function (Panier $record, array $data): void {
                        abort_unless(
                            auth()->user()?->canUsePermission('reservations.create'),
                            403
                        );

                        $nombre = (int) $data['nombre'];

                        DB::transaction(function () use ($record, $nombre): void {
                            $panier = Panier::query()
                                ->whereKey($record->id)
                                ->lockForUpdate()
                                ->firstOrFail();

                            if (! $panier->actif) {
                                throw ValidationException::withMessages([
                                    'nombre' => 'Ce panier n’est pas réservable.',
                                ]);
                            }

                            $composition = $panier->produits()
                                ->get()
                                ->map(fn (Produit $produit): array => [
                                    'produit_id' => $produit->id,
                                    'nom' => $produit->nom,
                                    'quantite' => (float) $produit->pivot->quantite,
                                ])
                                ->all();

                            if (count($composition) === 0) {
                                throw ValidationException::withMessages([
                                    'nombre' => 'Ajoute au moins un ingrédient à ce panier avant de le réserver.',
                                ]);
                            }

                            $faisables = self::calculerPaniersFaisables($panier);

                            if ($nombre > $faisables) {
                                throw ValidationException::withMessages([
                                    'nombre' => "Seulement {$faisables} panier(s) supplémentaire(s) peuvent être réservés avec les stocks disponibles.",
                                ]);
                            }

                            PanierReservation::create([
                                'panier_id' => $panier->id,
                                'nombre' => $nombre,
                                'nombre_prepare' => 0,
                                'statut' => 'reserve',
                                'composition' => $composition,
                            ]);
                        });

                        Notification::make()
                            ->title('Réservation enregistrée')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    /**
     * Calcule le nombre de paniers supplémentaires réalisables
     * en tenant compte des réservations qui ne sont pas encore préparées.
     */
    private static function calculerPaniersFaisables(Panier $panier): int
    {
        $composition = $panier->produits()->get();

        if ($composition->isEmpty()) {
            return 0;
        }

        $reservations = PanierReservation::query()
            ->where('panier_id', $panier->id)
            ->where('statut', 'reserve')
            ->get();

        $maximum = null;

        foreach ($composition as $produit) {
            $quantiteParPanier = (float) $produit->pivot->quantite;

            if ($quantiteParPanier <= 0) {
                return 0;
            }

            $quantiteReservee = 0.0;

            foreach ($reservations as $reservation) {
                $resteApreparer = max(
                    0,
                    $reservation->nombre - $reservation->nombre_prepare
                );

                foreach ($reservation->composition ?? [] as $ingredient) {
                    if ($ingredient['produit_id'] === $produit->id) {
                        $quantiteReservee +=
                            (float) ($ingredient['quantite'] ?? 0)
                            * $resteApreparer;
                    }
                }
            }

            $stockDisponible = max(
                0,
                (float) $produit->stock - $quantiteReservee
            );

            $faisablesPourCeProduit = (int) floor(
                ($stockDisponible + 0.000001) / $quantiteParPanier
            );

            $maximum = $maximum === null
                ? $faisablesPourCeProduit
                : min($maximum, $faisablesPourCeProduit);
        }

        return $maximum ?? 0;
    }
}
