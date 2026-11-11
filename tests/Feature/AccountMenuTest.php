<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_account_icon_and_google_sign_in_option(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Ouvrir le menu du compte')
            ->assertSee('Se connecter');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Continuer avec Google');
    }

    public function test_client_sees_account_links_without_staff_panels(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)->get(route('home'))
            ->assertOk()
            ->assertSee('Mes commandes')
            ->assertSee('Mes informations')
            ->assertSee('Déconnexion')
            ->assertDontSee('Administration')
            ->assertDontSee('Espace préparation')
            ->assertDontSee('Site client');
    }

    public function test_admin_sees_admin_link_and_preparator_sees_preparation_link(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('home'))
            ->assertOk()
            ->assertSee('Administration');

        $preparator = User::factory()->create();
        $preparator->syncPermissions(['preparations.view']);

        $this->actingAs($preparator)->get(route('home'))
            ->assertOk()
            ->assertSee('Espace préparation')
            ->assertDontSee('Administration');
    }
}
