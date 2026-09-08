<?php

namespace Tests\Feature\Events;

use App\Actions\Events\CreateEvent;
use App\Actions\Events\UpdateEvent;
use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Livewire\Events\EventForm;
use App\Livewire\Events\EventIndex;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class EventCrudTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function makeOrgWithMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $membership = $org->memberships()->create([
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
        ]);
        session(['current_organization_id' => $org->id]);

        return [$user, $org, $membership];
    }

    // ─── CreateEvent Action ─────────────────────────────────────────────────

    public function test_owner_can_create_event(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);

        $action = app(CreateEvent::class);
        $event = $action->handle($org, $owner, 'Test Summit');

        $this->assertDatabaseHas('events', [
            'organization_id' => $org->id,
            'name' => 'Test Summit',
            'status' => EventStatus::Draft->value,
            'created_by_user_id' => $owner->id,
        ]);
        $this->assertSame(EventStatus::Draft, $event->status);
    }

    public function test_admin_can_create_event(): void
    {
        [$admin, $org] = $this->makeOrgWithMember(OrganizationRole::Admin);

        $event = app(CreateEvent::class)->handle($org, $admin, 'Admin Event');

        $this->assertDatabaseHas('events', ['name' => 'Admin Event', 'organization_id' => $org->id]);
    }

    public function test_event_manager_cannot_create_event(): void
    {
        [$manager, $org] = $this->makeOrgWithMember(OrganizationRole::EventManager);

        $this->actingAs($manager);
        $this->expectException(AuthorizationException::class);

        app(CreateEvent::class)->handle($org, $manager, 'Should Fail');
    }

    public function test_create_event_validates_empty_name(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);

        $this->expectException(ValidationException::class);
        app(CreateEvent::class)->handle($org, $owner, '   ');
    }

    public function test_create_event_validates_end_before_start(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);

        $this->expectException(ValidationException::class);
        app(CreateEvent::class)->handle($org, $owner, 'Bad Dates', [
            'start_at' => now()->addDays(5)->toDateTimeString(),
            'end_at' => now()->addDays(2)->toDateTimeString(),
        ]);
    }

    public function test_create_event_validates_negative_capacity(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);

        $this->expectException(ValidationException::class);
        app(CreateEvent::class)->handle($org, $owner, 'Event', ['capacity' => -1]);
    }

    // ─── UpdateEvent Action ─────────────────────────────────────────────────

    public function test_owner_can_update_draft_event(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['status' => EventStatus::Draft, 'name' => 'Old Name']);

        app(UpdateEvent::class)->handle($event, $owner, ['name' => 'New Name']);

        $this->assertDatabaseHas('events', ['id' => $event->id, 'name' => 'New Name']);
    }

    public function test_event_manager_can_update_assigned_event(): void
    {
        [$manager, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::EventManager);
        $event = Event::factory()->for($org)->create(['status' => EventStatus::Draft]);

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        app(UpdateEvent::class)->handle($event, $manager, ['name' => 'Updated by Manager']);

        $this->assertDatabaseHas('events', ['id' => $event->id, 'name' => 'Updated by Manager']);
    }

    public function test_staff_cannot_update_event_even_if_assigned(): void
    {
        [$staff, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::Staff);
        $event = Event::factory()->for($org)->create(['status' => EventStatus::Draft]);

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        $this->actingAs($staff);
        $this->expectException(AuthorizationException::class);

        app(UpdateEvent::class)->handle($event, $staff, ['name' => 'Hacked']);
    }

    // ─── EventIndex Livewire ────────────────────────────────────────────────

    public function test_event_index_shows_all_events_for_owner(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        Event::factory()->for($org)->count(3)->create();

        Livewire::actingAs($owner)
            ->test(EventIndex::class)
            ->assertSee($org->events()->first()->name);
    }

    public function test_event_index_is_paginated(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);

        foreach (range(1, 16) as $i) {
            Event::factory()->for($org)->create([
                'name' => sprintf('Pagination Event %02d', $i),
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $this->actingAs($owner)
            ->get(route('app.events.index'))
            ->assertOk()
            ->assertSee('Pagination Event 01')
            ->assertDontSee('Pagination Event 16');

        $this->actingAs($owner)
            ->get(route('app.events.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Pagination Event 16')
            ->assertDontSee('Pagination Event 01');
    }

    public function test_event_index_only_shows_assigned_events_for_staff(): void
    {
        [$staff, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::Staff);

        $assigned = Event::factory()->for($org)->create(['name' => 'Assigned Event']);
        $unassigned = Event::factory()->for($org)->create(['name' => 'Hidden Event']);

        $assigned->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        Livewire::actingAs($staff)
            ->test(EventIndex::class)
            ->assertSee('Assigned Event')
            ->assertDontSee('Hidden Event');
    }

    public function test_event_index_does_not_show_other_org_events(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $otherEvent = Event::factory()->for($otherOrg)->create(['name' => 'Other Org Event']);

        Livewire::actingAs($owner)
            ->test(EventIndex::class)
            ->assertDontSee('Other Org Event');
    }

    // ─── EventForm Livewire ─────────────────────────────────────────────────

    public function test_owner_can_create_event_via_form(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);

        Livewire::actingAs($owner)
            ->test(EventForm::class)
            ->set('name', 'My New Event')
            ->set('mode', 'online')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('events', [
            'name' => 'My New Event',
            'organization_id' => $org->id,
            'status' => EventStatus::Draft->value,
        ]);
    }

    public function test_form_validates_required_name(): void
    {
        [$owner] = $this->makeOrgWithMember(OrganizationRole::Owner);

        Livewire::actingAs($owner)
            ->test(EventForm::class)
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_form_validates_end_before_start(): void
    {
        [$owner] = $this->makeOrgWithMember(OrganizationRole::Owner);

        Livewire::actingAs($owner)
            ->test(EventForm::class)
            ->set('name', 'Bad Event')
            ->set('start_at', '2030-12-01T10:00')
            ->set('end_at', '2030-11-01T10:00')
            ->call('save')
            ->assertHasErrors(['end_at']);
    }

    public function test_owner_can_edit_existing_event_via_form(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['name' => 'Original Name']);

        Livewire::actingAs($owner)
            ->test(EventForm::class, ['event' => $event])
            ->set('name', 'Updated Name')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('events', ['id' => $event->id, 'name' => 'Updated Name']);
    }

    public function test_cross_org_event_edit_is_blocked(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $otherEvent = Event::factory()->for($otherOrg)->create();

        Livewire::actingAs($owner)
            ->test(EventForm::class, ['event' => $otherEvent])
            ->assertStatus(404);
    }

    // ─── EventSetup (detail/setup shell) ──────────────────────────────────

    public function test_owner_can_view_event_setup_with_assignment_manager(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['name' => 'Setup Event']);

        $this->actingAs($owner)
            ->get(route('app.events.setup', $event))
            ->assertOk()
            ->assertSee('Setup Event')
            ->assertSee('Assign members to this event.')
            ->assertSee('Edit Event');
    }

    public function test_assigned_event_manager_can_view_setup_without_assignment_manager(): void
    {
        [$manager, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::EventManager);
        $event = Event::factory()->for($org)->create(['name' => 'Managed Setup Event']);

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        $this->actingAs($manager)
            ->get(route('app.events.setup', $event))
            ->assertOk()
            ->assertSee('Managed Setup Event')
            ->assertDontSee('Assign members to this event.')
            ->assertSee('Edit Event');
    }

    public function test_unassigned_staff_cannot_view_event_setup(): void
    {
        [$staff, $org] = $this->makeOrgWithMember(OrganizationRole::Staff);
        $event = Event::factory()->for($org)->create(['name' => 'Hidden Setup Event']);

        $this->actingAs($staff)
            ->get(route('app.events.setup', $event))
            ->assertForbidden();
    }

    public function test_cross_org_event_setup_is_blocked(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $otherEvent = Event::factory()->for($otherOrg)->create(['name' => 'Other Setup Event']);

        $this->actingAs($owner)
            ->get(route('app.events.setup', $otherEvent))
            ->assertNotFound();
    }
}
