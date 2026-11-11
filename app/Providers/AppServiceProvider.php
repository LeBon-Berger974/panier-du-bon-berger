<?php

namespace App\Providers;

use App\Models\Panier;
use App\Models\Produit;
use App\Models\User;
use App\Policies\PanierPolicy;
use App\Policies\ProduitPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\DevCommands;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureEmailVerificationNotification();

        if ($this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            DevCommands::artisan('schedule:work', 'schedule');
        }

        Gate::policy(Panier::class, PanierPolicy::class);
        Gate::policy(Produit::class, ProduitPolicy::class);
    }

    protected function configureEmailVerificationNotification(): void
    {
        VerifyEmail::toMailUsing(function (User $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Confirme ton adresse e-mail – Le Panier du Bon Berger')
                ->greeting('Bonjour '.$notifiable->name.',')
                ->line('Merci d’avoir créé un compte sur Le Panier du Bon Berger.')
                ->line('Clique sur le bouton ci-dessous pour confirmer ton adresse e-mail et activer ton compte.')
                ->action('Confirmer mon adresse e-mail', $url)
                ->line('Si tu n’as pas créé de compte, tu peux ignorer ce message.');
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
                ? Password::min(12)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
                : null,
        );
    }
}
