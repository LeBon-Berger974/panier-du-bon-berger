<x-layouts::auth :title="__('Créer un compte')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Créer ton compte')" :description="__('Tes informations te permettront de retrouver tes commandes.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-google-sign-in-button />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Nom complet -->
            <flux:input
                name="name"
                :label="__('Nom complet')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Prénom et nom')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Adresse e-mail')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="nom@exemple.fr"
            />

            <!-- Mot de passe -->
            <flux:input
                name="password"
                :label="__('Mot de passe')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Mot de passe')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Mot de passe -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirmer le mot de passe')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirmer le mot de passe')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Créer mon compte') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Tu as déjà un compte ?') }}</span>
            <flux:link
                :href="route('login')"
                data-test="login-link"
                wire:navigate
            >
                {{ __('Se connecter') }}
            </flux:link>
        </div>
    </div>
</x-layouts::auth>
