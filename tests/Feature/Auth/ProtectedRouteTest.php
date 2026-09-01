<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\TestCase;

class ProtectedRouteTest extends TestCase
{
    use InteractsWithOrganizations;
    use RefreshDatabase;

    public function test_guests_are_redirected_from_dashboard(): void
    {
        $this->get(route('app.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $organization = $this->createOrganizationForUser($user);

        $this->actingAs($user)
            ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
            ->get(route('app.dashboard'))
            ->assertOk()
            ->assertSee($user->name, false);
    }
}
