<?php

namespace App\Filament\Admin\Resources\ConfigurationsPaniers\Schemas;

use App\Models\Produit;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ConfigurationPanierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Panier')
                ->description('Modifiez librement le contenu et la présentation du panier. La faisabilité est indiquée séparément et ne bloque pas la pré-configuration.')
                ->columns(2)
                ->schema([
                    TextInput::make('nom')->label('Nom du panier')->required()->maxLength(255),
                    TextInput::make('prix_ht')->label('Prix de vente HT (€)')->numeric()->minValue(0.01)->step(0.01)->required(),
                    FileUpload::make('photo')->label('Photo du panier')->image()->disk('public')->directory('configurations-paniers')->visibility('public')->imageEditor()->maxSize(5120),
                    Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                ]),
            Section::make('Publication sur le site')
                ->columns(2)
                ->schema([
                    Select::make('publication_mode')->label('Mode d’activation')->options([
                        'manuel' => 'Manuel : bouton activer / désactiver',
                        'plage' => 'Automatique : plage de dates',
                    ])->default('manuel')->live()->required(),
                    Toggle::make('publie')->label('Activer ce panier sur le site')->default(false)
                        ->visible(fn (Get $get): bool => $get('publication_mode') !== 'plage')
                        ->helperText('Désactivez le bouton pour retirer le panier du catalogue.'),
                    DatePicker::make('publication_du')->label('Afficher à partir du')->native(false)
                        ->visible(fn (Get $get): bool => $get('publication_mode') === 'plage')
                        ->required(fn (Get $get): bool => $get('publication_mode') === 'plage')
                        ->rule('required_if:publication_mode,plage'),
                    DatePicker::make('publication_au')->label('Retirer du site après le')->native(false)
                        ->visible(fn (Get $get): bool => $get('publication_mode') === 'plage')
                        ->required(fn (Get $get): bool => $get('publication_mode') === 'plage')
                        ->rule('required_if:publication_mode,plage')
                        ->rule('after_or_equal:publication_du')
                        ->validationMessages(['after_or_equal' => 'La date de fin doit être égale ou postérieure à la date de début.']),
                ]),
            Section::make('Composition')
                ->description('Ajoutez les produits un par un, choisissez-les dans le stock et indiquez la quantité par panier. Le stock ne change pas ici.')
                ->schema([
                    Repeater::make('composition')
                        ->label('Produits choisis')
                        ->schema([
                            Select::make('produit_id')
                                ->label('Produit du stock')
                                ->options(fn (): array => Produit::query()->orderBy('nom')->get()
                                    ->mapWithKeys(fn (Produit $produit): array => [
                                        $produit->id => $produit->nom.' · stock : '.rtrim(rtrim(number_format((float) $produit->stock, 2, ',', ' '), '0'), ',').' '.$produit->unite,
                                    ])
                                    ->all())
                                ->searchable()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->required(),
                            TextInput::make('quantite')
                                ->label('Quantité par panier')
                                ->numeric()
                                ->minValue(0.001)
                                ->required(),
                        ])
                        ->defaultItems(0)
                        ->addActionLabel('Ajouter un produit')
                        ->columns(2)
                        ->reorderable(false)
                        ->minItems(1),
                ]),
        ]);
    }
}
