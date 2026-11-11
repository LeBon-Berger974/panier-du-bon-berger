<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_google_profile_can_create_and_authenticate_a_client_account(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-access-token'], 200),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-user-123',
                'email' => 'google@example.test',
                'email_verified' => true,
                'name' => 'Google Client',
                'picture' => 'https://lh3.googleusercontent.com/a/photo.png',
            ], 200),
        ]);

        $response = $this->withSession(['google_oauth_state' => 'valid-state'])
            ->get(route('auth.google.callback', ['state' => 'valid-state', 'code' => 'authorization-code']));

        $response->assertRedirect(route('boutique.commandes'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'google@example.test',
            'google_id' => 'google-user-123',
            'google_avatar_url' => 'https://lh3.googleusercontent.com/a/photo.png',
        ]);
        $this->assertDatabaseHas('roles', ['name' => 'client']);
    }

    public function test_google_callback_rejects_a_state_mismatch(): void
    {
        Http::preventStrayRequests();

        $response = $this->withSession(['google_oauth_state' => 'expected-state'])
            ->get(route('auth.google.callback', ['state' => 'different-state', 'code' => 'authorization-code']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertGuest();
    }
}
