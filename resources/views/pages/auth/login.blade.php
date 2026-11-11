<x-layouts::auth :title="__('Connexion')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Connexion à ton compte')" :description="__('Retrouve tes commandes et réserve ton panier.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-google-sign-in-button />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Adresse e-mail')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="nom@exemple.fr"
            />

            <!-- Mot de passe -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Mot de passe')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Mot de passe')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Mot de passe oublié ?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Se souvenir de moi')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Connexion') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Tu n’as pas encore de compte ?') }}</span>
            <flux:link
                :href="route('register')"
                data-test="register-link"
                wire:navigate
            >
                {{ __('Créer un compte') }}
            </flux:link>
        </div>
    </div>
</x-layouts::auth>
