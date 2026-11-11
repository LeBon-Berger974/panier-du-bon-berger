<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Resources\Commandes\CommandeResource;
use App\Filament\Admin\Resources\Produits\ProduitResource;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PreparateurPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('preparateur')
            ->path('prepa')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->darkMode(true, true)
            ->themeSwitcher(false)
            ->userMenuItems([
                'site-client' => Action::make('site-client')->label('Site client')->icon('heroicon-o-globe-alt')->url(fn (): string => route('home'))->visible(fn (): bool => auth()->user()?->hasRole('admin') ?? false),
                'admin' => Action::make('admin')->label('Administration')->icon('heroicon-o-cog-6-tooth')->url(url('/admin'))->visible(fn (): bool => auth()->user()?->hasRole('admin') ?? false),
                'orders' => Action::make('orders')->label('Mes commandes')->icon('heroicon-o-shopping-bag')->url(fn (): string => route('boutique.commandes')),
                'profile' => Action::make('profile')->label('Mes informations')->icon('heroicon-o-user-circle')->url(fn (): string => route('profile.edit')),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->resources([
                CommandeResource::class,
                ProduitResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Preparateur/Pages'), for: 'App\Filament\Preparateur\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Preparateur/Widgets'), for: 'App\Filament\Preparateur\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
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
