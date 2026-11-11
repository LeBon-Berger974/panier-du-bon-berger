<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\AdminDashboard;
use App\Filament\Admin\Resources\Commandes\CommandeResource;
use App\Filament\Admin\Resources\ConfigurationsPaniers\ConfigurationPanierResource;
use App\Filament\Admin\Resources\PointRelais\PointRelaisResource;
use App\Filament\Admin\Resources\Produits\ProduitResource;
use App\Filament\Admin\Resources\SessionsLivraison\SessionsLivraisonResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Widgets\AdminStatsWidget;
use App\Filament\Admin\Widgets\ActiviteRecenteWidget;
use App\Filament\Admin\Widgets\LivraisonsApercuWidget;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->brandName('🧺 Le Panier du Bon Berger ( Admin )')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->darkMode(true, true)
            ->themeSwitcher(false)
            ->userMenuItems([
                'site-client' => Action::make('site-client')->label('Site client')->icon('heroicon-o-globe-alt')->url(fn (): string => route('home')),
                'orders' => Action::make('orders')->label('Mes commandes')->icon('heroicon-o-shopping-bag')->url(fn (): string => route('boutique.commandes')),
                'profile' => Action::make('profile')->label('Mes informations')->icon('heroicon-o-user-circle')->url(fn (): string => route('profile.edit')),
                'preparation' => Action::make('preparation')->label('Espace préparation')->icon('heroicon-o-clipboard-document-list')->url(url('/prepa'))->visible(fn (): bool => (auth()->user()->hasRole('preparateur') && auth()->user()->hasAnyPermission()) || (auth()->user()->canConsulterCommandesRelais())),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->resources([
                ConfigurationPanierResource::class,
                SessionsLivraisonResource::class,
                CommandeResource::class,
                PointRelaisResource::class,
                ProduitResource::class,
                UserResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                AdminDashboard::class,
            ])
            ->widgets([
                AdminStatsWidget::class,
                LivraisonsApercuWidget::class,
                ActiviteRecenteWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
