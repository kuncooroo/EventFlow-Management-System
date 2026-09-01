<?php

namespace Tests\Feature\Organizations;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use InteractsWithOrganizations;
    use RefreshDatabase;

    public function test_authenticated_user_without_organization_is_redirected_to_create(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('app.dashboard'))
            ->assertRedirect(route('app.organizations.create'));
    }

    public function test_user_can_view_create_organization_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('app.organizations.create'))
            ->assertOk()
            ->assertSee('Create your organization', false)
            ->assertSee('Organization name', false);
    }

    public function test_user_can_create_organization_and_becomes_owner(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('app.organizations.store'), [
            'name' => 'Acme Events',
        ]);

        $response
            ->assertRedirect(route('app.dashboard'))
            ->assertSessionHas('status');

        $organization = Organization::query()->where('name', 'Acme Events')->first();

        $this->assertNotNull($organization);
        $this->assertSame('acme-events', $organization->slug);

        $membership = OrganizationMembership::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($membership);
        $this->assertSame(OrganizationRole::Owner, $membership->role);
        $this->assertNull($membership->removed_at);
        $this->assertSame($organization->id, session(OrganizationContext::SESSION_KEY));
    }

    public function test_create_organization_generates_unique_slug_on_collision(): void
    {
        Organization::factory()->create([
            'name' => 'Acme Events',
            'slug' => 'acme-events',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('app.organizations.store'), [
            'name' => 'Acme Events',
        ])->assertRedirect(route('app.dashboard'));

        $this->assertDatabaseHas('organizations', [
            'name' => 'Acme Events',
            'slug' => 'acme-events-2',
        ]);
    }

    public function test_user_with_organization_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $organization = $this->createOrganizationForUser($user, ['name' => 'Workspace One']);

        $this->actingAs($user)
            ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
            ->get(route('app.dashboard'))
            ->assertOk()
            ->assertSee('Workspace One', false);
    }

    public function test_switcher_lists_only_accessible_organizations(): void
    {
        $user = User::factory()->create();
        $orgA = $this->createOrganizationForUser($user, ['name' => 'Org Alpha']);
        $orgB = $this->createOrganizationForUser($user, ['name' => 'Org Beta']);
        $foreignOrg = Organization::factory()->create(['name' => 'Foreign Org']);

        OrganizationMembership::factory()->create([
            'organization_id' => $foreignOrg->id,
            'user_id' => User::factory()->create()->id,
            'role' => OrganizationRole::Owner,
        ]);

        $this->actingAs($user)
            ->withSession([OrganizationContext::SESSION_KEY => $orgA->id])
            ->get(route('app.dashboard'))
            ->assertOk()
            ->assertSee('Org Alpha', false)
            ->assertSee('Org Beta', false)
            ->assertDontSee('Foreign Org', false);
    }

    public function test_user_can_switch_to_accessible_organization(): void
    {
        $user = User::factory()->create();
        $orgA = $this->createOrganizationForUser($user, ['name' => 'Org Alpha']);
        $orgB = $this->createOrganizationForUser($user, ['name' => 'Org Beta']);

        $this->actingAs($user)
            ->withSession([OrganizationContext::SESSION_KEY => $orgA->id])
            ->post(route('app.organizations.switch'), [
                'organization_id' => $orgB->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame($orgB->id, session(OrganizationContext::SESSION_KEY));
    }

    public function test_user_cannot_switch_to_foreign_organization(): void
    {
        $user = User::factory()->create();
        $this->createOrganizationForUser($user);

        $foreignOrg = Organization::factory()->create(['name' => 'Foreign Org']);
        OrganizationMembership::factory()->owner()->create([
            'organization_id' => $foreignOrg->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $this->actingAs($user)
            ->post(route('app.organizations.switch'), [
                'organization_id' => $foreignOrg->id,
            ])
            ->assertSessionHasErrors('organization_id');
    }

    public function test_stale_session_organization_is_replaced_with_accessible_one(): void
    {
        $user = User::factory()->create();
        $organization = $this->createOrganizationForUser($user, ['name' => 'Valid Org']);
        $removedOrg = Organization::factory()->create(['name' => 'Removed Org']);

        $this->actingAs($user)
            ->withSession([OrganizationContext::SESSION_KEY => $removedOrg->id])
            ->get(route('app.dashboard'))
            ->assertOk()
            ->assertSee('Valid Org', false);

        $this->assertSame($organization->id, session(OrganizationContext::SESSION_KEY));
    }

    public function test_create_organization_requires_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('app.organizations.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('organizations', 0);
    }
}
