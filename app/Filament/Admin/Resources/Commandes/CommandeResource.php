<?php

namespace App\Filament\Admin\Resources\Commandes;

use App\Filament\Admin\Resources\Commandes\Pages\ManageCommandes;
use App\Models\Commande;
use App\Models\PointRelais;
use App\Models\RecompenseRelais;
use App\Models\SessionLivraison;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommandeResource extends Resource
{
    protected static ?string $model = Commande::class;

    protected static ?string $navigationLabel = 'Commandes';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'commande';

    protected static ?string $pluralModelLabel = 'commandes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function canViewAny(): bool
    {
        return (auth()->user()?->canUsePermission('relay.orders.view') ?? false)
            || (auth()->user()?->canUsePermission('baskets.view') ?? false)
            || (auth()->user()?->canUsePermission('preparations.view') ?? false);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (Filament::getCurrentPanel()?->getId() === 'preparateur' && $user?->hasRole('responsable_relais')) {
            $ids = $user->pointsRelaisAffectes()->pluck('points_relais.id');
            $query->where('mode_retrait', 'relais')->whereIn('point_relais_id', $ids);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('reference')->label('N°')->searchable()->sortable(),
            TextColumn::make('client.name')->label('Client')->searchable(),
            TextColumn::make('configuration_panier_nom')->label('Configuration')->placeholder('Panier historique')->searchable(),
            TextColumn::make('sessionLivraison.date_livraison')->label('Livraison')->date('d/m/Y')->sortable(),
            TextColumn::make('mode_retrait')->label('Retrait')->formatStateUsing(fn (string $state): string => $state === 'ferme' ? 'Ferme' : 'Point relais'),
            TextColumn::make('pointRelais.nom')->label('Lieu'),
            TextColumn::make('relais_total')->label('Paniers au relais')
                ->state(fn (Commande $record): int => $record->point_relais_id
                    ? (int) Commande::query()
                        ->where('session_livraison_id', $record->session_livraison_id)
                        ->where('point_relais_id', $record->point_relais_id)
                        ->whereIn('statut', [Commande::PREPAREE, Commande::AU_RELAIS, Commande::RETIREE])
                        ->sum('nombre_paniers')
                    : 0)
                ->visible(fn (?Commande $record): bool => $record?->mode_retrait === 'relais'),
            TextColumn::make('nombre_paniers')->label('Paniers'),
            TextColumn::make('montant_consigne_ht')->label('Consigne')->state(fn (Commande $record): string => (float) $record->montant_consigne_ht > 0 ? '1re fois' : '—'),
            TextColumn::make('montant_panier_ht')->label('Montant HT')->money('EUR'),
            TextColumn::make('statut')->label('Statut')->formatStateUsing(fn (Commande $record): string => $record->statut_label)->badge()
                ->color(fn (Commande $record): string => match ($record->statut) {
                    'payee' => 'success', 'preparee' => 'warning', 'au_relais' => 'info', 'retiree' => 'gray', 'annulee' => 'danger', default => 'gray'
                }),
        ])->filters([
            SelectFilter::make('session_livraison_id')
                ->label('Session de livraison')
                ->relationship('sessionLivraison', 'date_livraison')
                ->searchable()
                ->preload(),
            SelectFilter::make('point_relais_id')
                ->label('Point relais')
                ->relationship('pointRelais', 'nom')
                ->searchable()
                ->preload(),
            SelectFilter::make('statut')
                ->label('Statut')
                ->options([
                    Commande::A_PAYER => 'À payer',
                    Commande::PAYEE => 'Payée',
                    Commande::PREPAREE => 'Préparée',
                    Commande::AU_RELAIS => 'Au relais',
                    Commande::RETIREE => 'Retirée',
                    Commande::ANNULEE => 'Annulée',
                ]),
        ])->defaultSort('created_at', 'desc')->recordActions([
            Action::make('preparer')->label('Marquer préparée')->color('success')
                ->visible(fn (Commande $record): bool => ! (auth()->user()?->hasRole('responsable_relais') ?? false) && (auth()->user()?->canUsePermission('preparations.create') ?? false) && $record->statut === Commande::PAYEE)
                ->action(fn (Commande $record) => $record->update(['statut' => Commande::PREPAREE, 'preparee_at' => now()])),
            Action::make('deposer')->label('Remise au relais')->color('info')
                ->visible(fn (Commande $record): bool => ! (auth()->user()?->hasRole('responsable_relais') ?? false) && (auth()->user()?->canUsePermission('preparations.create') ?? false) && $record->statut === Commande::PREPAREE && $record->mode_retrait === 'relais')
                ->action(function (Commande $record): void {
                    DB::transaction(function () use ($record): void {
                        $session = SessionLivraison::query()->whereKey($record->session_livraison_id)->lockForUpdate()->firstOrFail();
                        $relais = PointRelais::query()->whereKey($record->point_relais_id)->lockForUpdate()->firstOrFail();
                        $groupe = Commande::query()
                            ->where('session_livraison_id', $session->id)
                            ->where('point_relais_id', $relais->id)
                            ->where('mode_retrait', 'relais')
                            ->whereIn('statut', [Commande::PREPAREE, Commande::AU_RELAIS, Commande::RETIREE]);
                        $totalPrepareOuLivre = (int) $groupe->sum('nombre_paniers');
                        if ($totalPrepareOuLivre < $relais->minimum_paniers) {
                            throw ValidationException::withMessages([
                                'point_relais_id' => 'Le minimum de '.$relais->minimum_paniers.' paniers pour cette livraison n’est pas encore atteint.',
                            ]);
                        }

                        $commande = Commande::query()->lockForUpdate()->findOrFail($record->id);
                        if ($commande->statut !== Commande::PREPAREE) {
                            throw ValidationException::withMessages(['statut' => 'Cette commande n’est plus en attente de remise au relais.']);
                        }
                        $commande->update(['statut' => Commande::AU_RELAIS, 'remise_relais_at' => now()]);

                        $totalLivre = (int) Commande::query()
                            ->where('session_livraison_id', $session->id)
                            ->where('point_relais_id', $relais->id)
                            ->where('mode_retrait', 'relais')
                            ->whereIn('statut', [Commande::AU_RELAIS, Commande::RETIREE])
                            ->sum('nombre_paniers');

                        for ($palier = 1; $palier <= intdiv($totalLivre, 10); $palier++) {
                            RecompenseRelais::query()->firstOrCreate([
                                'point_relais_id' => $relais->id,
                                'session_livraison_id' => $session->id,
                                'numero_palier' => $palier,
                            ]);
                        }
                    });
                }),
            Action::make('confirmer_recompense')->label('Confirmer le panier offert')->color('success')->requiresConfirmation()
                ->visible(fn (Commande $record): bool => ! (auth()->user()?->hasRole('responsable_relais') ?? false) && (auth()->user()?->canUsePermission('preparations.create') ?? false) && $record->mode_retrait === 'relais'
                    && in_array($record->statut, [Commande::AU_RELAIS, Commande::RETIREE], true)
                    && RecompenseRelais::query()
                        ->where('point_relais_id', $record->point_relais_id)
                        ->where('session_livraison_id', $record->session_livraison_id)
                        ->where('statut', 'a_remettre')
                        ->exists())
                ->action(fn (Commande $record) => RecompenseRelais::query()
                    ->where('point_relais_id', $record->point_relais_id)
                    ->where('session_livraison_id', $record->session_livraison_id)
                    ->where('statut', 'a_remettre')
                    ->update(['statut' => 'remis', 'remis_at' => now()])),
            Action::make('retirer')->label('Confirmer le retrait')->color('primary')
                ->visible(fn (Commande $record): bool => ((auth()->user()?->canUsePermission('relay.pickup') ?? false) && $record->mode_retrait === 'relais' && $record->statut === Commande::AU_RELAIS)
                    || (! (auth()->user()?->hasRole('responsable_relais') ?? false) && (auth()->user()?->canUsePermission('preparations.create') ?? false) && $record->mode_retrait === 'ferme' && $record->statut === Commande::PREPAREE)
                    || (! (auth()->user()?->hasRole('responsable_relais') ?? false) && (auth()->user()?->canUsePermission('preparations.create') ?? false) && $record->mode_retrait === 'relais' && $record->statut === Commande::AU_RELAIS))
                ->form([
                    TextInput::make('nombre_contenants_retournes')
                        ->label('Contenants rendus aujourd’hui')
                        ->integer()
                        ->minValue(0)
                        ->maxValue(fn (Commande $record): int => max(0, $record->nombre_paniers - (int) $record->mouvementsConsignes()->whereIn('type', ['retour', 'perdu'])->sum('quantite')))
                        ->default(0)
                        ->required(),
                    TextInput::make('nombre_contenants_perdus')
                        ->label('Contenants perdus')
                        ->integer()
                        ->minValue(0)
                        ->maxValue(fn (Commande $record): int => max(0, $record->nombre_paniers - (int) $record->mouvementsConsignes()->whereIn('type', ['retour', 'perdu'])->sum('quantite')))
                        ->default(0)
                        ->required(),
                ])
                ->action(function (Commande $record, array $data): void {
                    DB::transaction(function () use ($record, $data): void {
                        $commande = Commande::query()->lockForUpdate()->findOrFail($record->id);
                        if ($commande->statut !== ($commande->mode_retrait === 'ferme' ? Commande::PREPAREE : Commande::AU_RELAIS)) {
                            throw ValidationException::withMessages(['statut' => 'Cette commande ne peut pas encore être retirée.']);
                        }
                        $quantiteRetournee = (int) ($data['nombre_contenants_retournes'] ?? 0);
                        $quantitePerdue = (int) ($data['nombre_contenants_perdus'] ?? 0);
                        $dejaComptabilises = (int) $commande->mouvementsConsignes()->whereIn('type', ['retour', 'perdu'])->sum('quantite');
                        if ($quantiteRetournee + $quantitePerdue + $dejaComptabilises > $commande->nombre_paniers) {
                            throw ValidationException::withMessages(['nombre_contenants_retournes' => 'Le nombre de contenants rendus et perdus dépasse le nombre emprunté.']);
                        }
                        if ($quantiteRetournee > 0) {
                            $commande->mouvementsConsignes()->create([
                                'type' => 'retour',
                                'quantite' => $quantiteRetournee,
                                'montant_unitaire' => 0,
                                'effectue_at' => now(),
                                'note' => 'Contenants rendus lors du retrait de la commande.',
                            ]);
                        }
                        if ($quantitePerdue > 0) {
                            $commande->mouvementsConsignes()->create([
                                'type' => 'perdu',
                                'quantite' => $quantitePerdue,
                                'montant_unitaire' => 0,
                                'effectue_at' => now(),
                            ]);
                        }
                        $commande->update(['statut' => Commande::RETIREE, 'retiree_at' => now()]);
                    });
                }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCommandes::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
