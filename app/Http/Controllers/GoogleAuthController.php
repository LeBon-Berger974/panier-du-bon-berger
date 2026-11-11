<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GoogleAuthController
{
    public function redirect(): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (! filled($clientId) || ! filled($clientSecret)) {
            return redirect()->route('login')->with('status', 'La connexion Google doit être configurée avant utilisation.');
        }

        $state = Str::random(48);
        session()->put('google_oauth_state', $state);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => config('services.google.redirect') ?: route('auth.google.callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]));
    }

    public function callback(Request $request): RedirectResponse
    {
        $expectedState = session()->pull('google_oauth_state');
        $providedState = $request->query('state');

        if (! is_string($expectedState) || ! is_string($providedState) || ! hash_equals($expectedState, $providedState)) {
            return redirect()->route('login')->with('status', 'La vérification Google a expiré. Réessaie de te connecter.');
        }

        if ($request->filled('error')) {
            return redirect()->route('login')->with('status', 'La connexion Google a été annulée.');
        }

        $code = $request->query('code');
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (! is_string($code) || ! filled($clientId) || ! filled($clientSecret)) {
            return redirect()->route('login')->with('status', 'La connexion Google ne peut pas aboutir pour le moment.');
        }

        try {
            $tokenResponse = Http::asForm()->acceptJson()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => config('services.google.redirect') ?: route('auth.google.callback'),
                'grant_type' => 'authorization_code',
            ]);

            if (! $tokenResponse->successful() || ! is_string($tokenResponse->json('access_token'))) {
                return redirect()->route('login')->with('status', 'Google n’a pas pu confirmer la connexion. Réessaie.');
            }

            $profileResponse = Http::withToken($tokenResponse->json('access_token'))
                ->acceptJson()
                ->get('https://openidconnect.googleapis.com/v1/userinfo');
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->with('status', 'Google est momentanément indisponible. Réessaie plus tard.');
        }

        $profile = $profileResponse->json();
        $googleId = $profile['sub'] ?? null;
        $email = $profile['email'] ?? null;
        $emailVerified = $profile['email_verified'] ?? false;

        if (! $profileResponse->successful() || ! is_string($googleId) || ! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! in_array($emailVerified, [true, 'true', 1, '1'], true)) {
            return redirect()->route('login')->with('status', 'Le compte Google doit avoir une adresse e-mail vérifiée.');
        }

        $user = User::query()->where('google_id', $googleId)->first();
        $matchingEmail = User::query()->where('email', $email)->first();

        if ($user && $matchingEmail && $user->isNot($matchingEmail)) {
            return redirect()->route('login')->with('status', 'Ce compte Google est déjà associé à un autre profil.');
        }

        $user ??= $matchingEmail;
        $name = is_string($profile['name'] ?? null) ? $profile['name'] : Str::before($email, '@');
        $avatar = is_string($profile['picture'] ?? null) ? $profile['picture'] : null;
        $avatarParts = $avatar ? parse_url($avatar) : false;
        $avatar = is_array($avatarParts)
            && ($avatarParts['scheme'] ?? null) === 'https'
            && str_ends_with($avatarParts['host'] ?? '', 'googleusercontent.com')
                ? $avatar
                : null;

        if (! $user) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'google_id' => $googleId,
                'google_avatar_url' => $avatar,
                'email_verified_at' => now(),
            ]);
            $user->assignRole('client');
        } else {
            $user->forceFill([
                'email' => $email,
                'google_id' => $googleId,
                'google_avatar_url' => $avatar,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        if (filled($user->two_factor_secret) && $user->two_factor_confirmed_at) {
            return redirect()->route('login')->with('status', 'Ce compte utilise la double authentification. Connecte-toi avec ton mot de passe pour la conserver.');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('boutique.commandes'));
    }
}
