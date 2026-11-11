<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\MouvementConsigne;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Nom complet'),

                TextEntry::make('email')
                    ->label('Adresse e-mail'),

                TextEntry::make('roles.label')
                    ->label('Rôles')
                    ->badge()
                    ->placeholder('Aucun rôle'),

                TextEntry::make('pointRelaisFavori.nom')
                    ->label('Point relais favori')
                    ->placeholder('Pas encore choisi'),

                TextEntry::make('contenants_pretes')
                    ->label('Contenants prêtés')
                    ->state(fn (User $record): int => self::totalContenants($record, ['pret', 'depot'])),

                TextEntry::make('contenants_rendus')
                    ->label('Contenants rendus')
                    ->state(fn (User $record): int => self::totalContenants($record, ['retour'])),

                TextEntry::make('contenants_perdus')
                    ->label('Contenants perdus')
                    ->state(fn (User $record): int => self::totalContenants($record, ['perdu'])),

                TextEntry::make('created_at')
                    ->label('Compte créé le')
                    ->dateTime('d/m/Y à H:i'),
            ]);
    }

    /** @param array<int, string> $types */
    private static function totalContenants(User $client, array $types): int
    {
        return (int) MouvementConsigne::query()
            ->whereIn('type', $types)
            ->whereHas('commande', fn ($query) => $query->where('user_id', $client->id)->whereNotIn('statut', ['a_payer', 'annulee']))
            ->sum('quantite');
    }
}
