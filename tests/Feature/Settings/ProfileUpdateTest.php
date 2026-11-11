<?php

namespace Tests\Feature\Settings;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.profile')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $user->refresh();

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.profile')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_non_admin_cannot_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'password')
            ->call('deleteUser');

        $response->assertStatus(403);
        $this->assertNotNull($user->fresh());
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_delete_their_account_with_the_correct_password(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrateur']));
        $this->actingAs($admin);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'password')
            ->call('deleteUser');

        $response->assertHasNoErrors()->assertRedirect('/');
        $this->assertNull($admin->fresh());
        $this->assertFalse(auth()->check());
    }

    public function test_admin_must_provide_the_correct_password_to_delete_their_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrateur']));
        $this->actingAs($admin);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $response->assertHasErrors(['password']);
        $this->assertNotNull($admin->fresh());
    }
}
