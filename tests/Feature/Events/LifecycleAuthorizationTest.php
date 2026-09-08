<?php

namespace Tests\Feature\Events;

use App\Actions\Events\ArchiveEvent;
use App\Actions\Events\CancelEvent;
use App\Actions\Events\CompleteEvent;
use App\Actions\Events\MarkEventOngoing;
use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Livewire\Events\EventIndex;
use App\Livewire\Events\EventSetup;
use App\Livewire\Events\LifecycleActions;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LifecycleAuthorizationTest extends TestCase
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

    private function assertDenied(string $action, object $event, User $user): void
    {
        $this->expectException(AuthorizationException::class);

        app($action)->handle($event, $user);
    }

    // ─── Assigned Event Manager can perform all transitions ────────────────

    public function test_assigned_event_manager_can_perform_all_transitions(): void
    {
        [$manager, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::EventManager);

        $published = Event::factory()->for($org)->published()->create();
        $published->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        $ongoing = Event::factory()->for($org)->ongoing()->create();
        $ongoing->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);
        $completed = Event::factory()->for($org)->completed()->create();
        $completed->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);
        $cancelled = Event::factory()->for($org)->cancelled()->create();
        $cancelled->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        (app(MarkEventOngoing::class))->handle($published, $manager);
        (app(CompleteEvent::class))->handle($ongoing, $manager);
        (app(CancelEvent::class))->handle($published->refresh(), $manager);
        (app(ArchiveEvent::class))->handle($completed, $manager);
        (app(ArchiveEvent::class))->handle($cancelled, $manager);

        $this->assertSame(EventStatus::Cancelled, $published->refresh()->status);
        $this->assertSame(EventStatus::Completed, $ongoing->refresh()->status);
        $this->assertSame(EventStatus::Archived, $completed->refresh()->status);
        $this->assertSame(EventStatus::Archived, $cancelled->refresh()->status);
    }

    // ─── Unassigned / low-privilege roles are denied ────────────────────────

    public function test_unassigned_event_manager_cannot_transition_events(): void
    {
        [$manager, $org] = $this->makeOrgWithMember(OrganizationRole::EventManager);
        $published = Event::factory()->for($org)->published()->create();

        $this->assertDenied(MarkEventOngoing::class, $published, $manager);
    }

    public function test_staff_cannot_transition_events_even_when_assigned(): void
    {
        [$staff, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::Staff);
        $event = Event::factory()->for($org)->published()->create();
        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        $this->assertDenied(CancelEvent::class, $event, $staff);
    }

    public function test_other_organization_owner_cannot_transition_events(): void
    {
        [$otherOwner] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $event = Event::factory()->for($otherOrg)->published()->create();

        $this->assertDenied(MarkEventOngoing::class, $event, $otherOwner);
    }

    public function test_unassigned_viewer_cannot_transition_events(): void
    {
        [$viewer, $org] = $this->makeOrgWithMember(OrganizationRole::Viewer);
        $event = Event::factory()->for($org)->published()->create();

        $this->assertDenied(ArchiveEvent::class, Event::factory()->for($org)->completed()->create(), $viewer);
    }

    // ─── Livewire UI ────────────────────────────────────────────────────────

    public function test_owner_sees_lifecycle_section_and_start_button(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->published()->create();

        Livewire::actingAs($owner)
            ->test(EventSetup::class, ['event' => $event])
            ->assertSee('Event Lifecycle')
            ->assertSee('Start Event');
    }

    public function test_assigned_staff_does_not_see_lifecycle_section(): void
    {
        [$staff, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::Staff);
        $event = Event::factory()->for($org)->published()->create();
        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        Livewire::actingAs($staff)
            ->test(EventSetup::class, ['event' => $event])
            ->assertDontSee('Event Lifecycle')
            ->assertDontSee('Cancel Event');
    }

    public function test_lifecycle_component_starts_a_published_event(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->published()->create();

        Livewire::actingAs($owner)
            ->test(LifecycleActions::class, ['event' => $event])
            ->call('startEvent')
            ->assertSee('Ongoing');

        $this->assertSame(EventStatus::Ongoing, $event->refresh()->status);
    }

    public function test_lifecycle_component_cancels_then_archives(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->ongoing()->create();

        Livewire::actingAs($owner)
            ->test(LifecycleActions::class, ['event' => $event])
            ->call('cancelEvent')
            ->assertSee('Cancelled');

        $this->assertSame(EventStatus::Cancelled, $event->refresh()->status);

        Livewire::actingAs($owner)
            ->test(LifecycleActions::class, ['event' => $event])
            ->call('archiveEvent')
            ->assertSee('Archived');

        $this->assertSame(EventStatus::Archived, $event->refresh()->status);
    }

    public function test_lifecycle_component_rejects_an_illegal_transition(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->draft()->create();

        Livewire::actingAs($owner)
            ->test(LifecycleActions::class, ['event' => $event])
            ->call('archiveEvent')
            ->assertHasNoErrors()
            ->assertStatus(200);

        $this->assertSame(EventStatus::Draft, $event->refresh()->status);
    }

    // ─── Archive hides from active lists (EBR-005) ──────────────────────────

    public function test_archived_events_are_hidden_from_default_index_but_filterable(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $active = Event::factory()->for($org)->published()->create(['name' => 'Active Event']);
        $completed = Event::factory()->for($org)->completed()->create(['name' => 'Completed Event']);
        $archived = Event::factory()->for($org)->archived()->create(['name' => 'Archived Event']);

        Livewire::actingAs($owner)
            ->test(EventIndex::class)
            ->assertSee('Active Event')
            ->assertSee('Completed Event')
            ->assertDontSee('Archived Event');

        Livewire::actingAs($owner)
            ->test(EventIndex::class, ['statusFilter' => EventStatus::Archived->value])
            ->assertSee('Archived Event')
            ->assertDontSee('Active Event');
    }
}
