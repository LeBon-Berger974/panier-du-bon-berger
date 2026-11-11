<?php

namespace App\Filament\Admin\Pages;

use App\Models\ReglagesSite;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use LogicException;

class Reglages extends Page
{
    protected string $view = 'filament.admin.pages.reglages';

    protected static ?string $title = 'Réglages';

    protected static ?string $navigationLabel = 'Réglages';

    protected static ?int $navigationSort = 10;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->settingsForm()->fill(ReglagesSite::valeurs());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Prix de panier (HT)')
                ->schema([
                    TextInput::make('prix_panier_ht')->label('Prix du panier (€ HT)')->numeric()->minValue(0)->required(),
                    TextInput::make('consigne_ht')->label('Consigne du contenant (€ HT)')->numeric()->minValue(0)->required()->helperText('Montant provisoire, à confirmer avec le fournisseur.'),
                    TextInput::make('taux_tva')->label('Taux de TVA (%)')->numeric()->minValue(0)->maxValue(100)->required()->helperText('Valeur à confirmer avec votre comptable.'),
                ])->columns(1),
            Section::make('Horaires des créneaux de réservation')
                ->description('Ces horaires sont proposés par défaut lors de la création d’une session. Ils restent modifiables pour chaque livraison.')
                ->columns(2)
                ->schema([
                    TimePicker::make('ouverture_reservations_mercredi')->label('Ouverture avant livraison du mercredi')->seconds(false),
                    TimePicker::make('cloture_reservations_mercredi')->label('Clôture avant livraison du mercredi')->seconds(false),
                    TimePicker::make('ouverture_reservations_vendredi')->label('Ouverture avant livraison du vendredi')->seconds(false),
                    TimePicker::make('cloture_reservations_vendredi')->label('Clôture avant livraison du vendredi')->seconds(false),
                ]),
            Section::make('Textes et e-mails')
                ->columns(1)
                ->schema([
                    TextInput::make('email_contact')->label('Adresse e-mail de contact')->email()->maxLength(255),
                    Textarea::make('texte_accueil')->label('Texte d’accueil du site')->rows(3),
                    TextInput::make('sujet_email_confirmation')->label('Objet de l’e-mail de confirmation')->maxLength(255),
                    Textarea::make('texte_email_confirmation')->label('Texte de l’e-mail de confirmation')->rows(3),
                    TextInput::make('sujet_email_rappel')->label('Objet de l’e-mail de rappel')->maxLength(255),
                    Textarea::make('texte_email_rappel')->label('Texte de l’e-mail de rappel')->rows(3),
                ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        $reglages = ReglagesSite::query()->firstOrNew(['id' => 1]);
        $reglages->fill($this->settingsForm()->getState())->save();
        $this->settingsForm()->fill($reglages->only([
            'prix_panier_ht', 'consigne_ht', 'taux_tva',
            'ouverture_reservations_mercredi', 'cloture_reservations_mercredi',
            'ouverture_reservations_vendredi', 'cloture_reservations_vendredi',
            'email_contact', 'texte_accueil', 'sujet_email_confirmation', 'texte_email_confirmation',
            'sujet_email_rappel', 'texte_email_rappel',
        ]));
        Notification::make()->title('Réglages enregistrés')->success()->send();
    }

    private function settingsForm(): Schema
    {
        return $this->getSchema('form') ?? throw new LogicException('Le schéma de réglages est indisponible.');
    }
}
