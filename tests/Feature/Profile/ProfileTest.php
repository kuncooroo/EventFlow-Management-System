<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_profile(): void
    {
        $this->get(route('app.profile.edit'))
            ->assertRedirect(route('login'));

        $this->put(route('app.profile.update'), [
            'name' => 'Guest User',
            'email' => 'guest@example.com',
        ])->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Taylor Organizer',
            'email' => 'taylor@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('app.profile.edit'))
            ->assertOk()
            ->assertSee('Your profile', false)
            ->assertSee('Taylor Organizer', false)
            ->assertSee('taylor@example.com', false)
            ->assertSee('Password cannot be changed on this page', false)
            ->assertDontSee('name="password"', false);
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($user)->put(route('app.profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response
            ->assertRedirect(route('app.profile.edit'))
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
    }

    public function test_profile_update_rejects_duplicate_email(): void
    {
        $existing = User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'mine@example.com']);

        $this->actingAs($user)->put(route('app.profile.update'), [
            'name' => 'Valid Name',
            'email' => $existing->email,
        ])->assertSessionHasErrors('email');

        $user->refresh();
        $this->assertSame('mine@example.com', $user->email);
    }

    public function test_profile_update_requires_name_and_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('app.profile.update'), [
            'name' => '',
            'email' => '',
        ])->assertSessionHasErrors(['name', 'email']);
    }

    public function test_profile_update_rejects_invalid_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('app.profile.update'), [
            'name' => 'Valid Name',
            'email' => 'not-an-email',
        ])->assertSessionHasErrors('email');
    }

    public function test_user_can_keep_their_current_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Same Email User',
            'email' => 'same@example.com',
        ]);

        $this->actingAs($user)->put(route('app.profile.update'), [
            'name' => 'Updated Name',
            'email' => 'same@example.com',
        ])->assertRedirect(route('app.profile.edit'));

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('same@example.com', $user->email);
    }
}
