<?php

namespace Tests\Feature\Events;

use App\Actions\Events\AssignMemberToEvent;
use App\Actions\Events\UnassignMemberFromEvent;
use App\Enums\OrganizationRole;
use App\Livewire\Events\AssignmentManager;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_member_to_event()
    {
        $owner = User::factory()->create();
        $organization = Organization::create(['name' => 'Test Org', 'slug' => 'test-org', 'timezone' => 'UTC', 'locale' => 'en']);
        $organization->memberships()->create(['user_id' => $owner->id, 'role' => OrganizationRole::Owner, 'joined_at' => now()]);

        $event = Event::create([
            'organization_id' => $organization->id,
            'name' => 'Test Event',
            'status' => 'draft',
        ]);

        $staffUser = User::factory()->create();
        $staffMembership = $organization->memberships()->create(['user_id' => $staffUser->id, 'role' => OrganizationRole::Staff, 'joined_at' => now()]);

        $action = new AssignMemberToEvent;

        session(['current_organization_id' => $organization->id]); // Mock OrganizationContext

        $action->handle($event, $staffMembership, $owner);

        $this->assertDatabaseHas('event_assignments', [
            'event_id' => $event->id,
            'organization_membership_id' => $staffMembership->id,
            'assigned_by_user_id' => $owner->id,
        ]);
    }

    public function test_unassign_member_from_event()
    {
        $owner = User::factory()->create();
        $organization = Organization::create(['name' => 'Test Org', 'slug' => 'test-org', 'timezone' => 'UTC', 'locale' => 'en']);
        $organization->memberships()->create(['user_id' => $owner->id, 'role' => OrganizationRole::Owner, 'joined_at' => now()]);

        $event = Event::create([
            'organization_id' => $organization->id,
            'name' => 'Test Event',
            'status' => 'draft',
        ]);

        $staffUser = User::factory()->create();
        $staffMembership = $organization->memberships()->create(['user_id' => $staffUser->id, 'role' => OrganizationRole::Staff, 'joined_at' => now()]);

        $event->assignments()->create([
            'organization_id' => $organization->id,
            'organization_membership_id' => $staffMembership->id,
            'assigned_by_user_id' => $owner->id,
        ]);

        $action = new UnassignMemberFromEvent;

        session(['current_organization_id' => $organization->id]); // Mock OrganizationContext

        $action->handle($event, $staffMembership, $owner);

        $this->assertDatabaseMissing('event_assignments', [
            'event_id' => $event->id,
            'organization_membership_id' => $staffMembership->id,
        ]);
    }

    public function test_unauthorized_user_cannot_assign()
    {
        $viewer = User::factory()->create();
        $organization = Organization::create(['name' => 'Test Org', 'slug' => 'test-org', 'timezone' => 'UTC', 'locale' => 'en']);
        $organization->memberships()->create(['user_id' => $viewer->id, 'role' => OrganizationRole::Viewer, 'joined_at' => now()]);

        $event = Event::create([
            'organization_id' => $organization->id,
            'name' => 'Test Event',
            'status' => 'draft',
        ]);

        $staffUser = User::factory()->create();
        $staffMembership = $organization->memberships()->create(['user_id' => $staffUser->id, 'role' => OrganizationRole::Staff, 'joined_at' => now()]);

        session(['current_organization_id' => $organization->id]); // Mock OrganizationContext

        $this->actingAs($viewer);

        $action = new AssignMemberToEvent;

        $this->expectException(AuthorizationException::class);

        $action->handle($event, $staffMembership, $viewer);
    }

    public function test_cross_org_assignment_is_prevented()
    {
        $owner = User::factory()->create();
        $org1 = Organization::create(['name' => 'Org 1', 'slug' => 'org-1', 'timezone' => 'UTC', 'locale' => 'en']);
        $org1->memberships()->create(['user_id' => $owner->id, 'role' => OrganizationRole::Owner, 'joined_at' => now()]);

        $event = Event::create([
            'organization_id' => $org1->id,
            'name' => 'Test Event',
            'status' => 'draft',
        ]);

        $org2 = Organization::create(['name' => 'Org 2', 'slug' => 'org-2', 'timezone' => 'UTC', 'locale' => 'en']);
        $staffUser = User::factory()->create();
        $staffMembership = $org2->memberships()->create(['user_id' => $staffUser->id, 'role' => OrganizationRole::Staff, 'joined_at' => now()]);

        $action = new AssignMemberToEvent;

        session(['current_organization_id' => $org1->id]);
        $this->actingAs($owner);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Assignee must belong to the same organization.');

        $action->handle($event, $staffMembership, $owner);
    }

    public function test_assignment_manager_livewire_component()
    {
        $owner = User::factory()->create();
        $organization = Organization::create(['name' => 'Test Org', 'slug' => 'test-org', 'timezone' => 'UTC', 'locale' => 'en']);
        $organization->memberships()->create(['user_id' => $owner->id, 'role' => OrganizationRole::Owner, 'joined_at' => now()]);

        $event = Event::create([
            'organization_id' => $organization->id,
            'name' => 'Test Event',
            'status' => 'draft',
        ]);

        $staffUser = User::factory()->create();
        $staffMembership = $organization->memberships()->create(['user_id' => $staffUser->id, 'role' => OrganizationRole::Staff, 'joined_at' => now()]);

        session(['current_organization_id' => $organization->id]);

        Livewire::actingAs($owner)
            ->test(AssignmentManager::class, ['event' => $event])
            ->assertSee($staffUser->name)
            ->set('assignee_id', $staffMembership->id)
            ->call('assignMember')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('event_assignments', [
            'event_id' => $event->id,
            'organization_membership_id' => $staffMembership->id,
        ]);
    }
}
