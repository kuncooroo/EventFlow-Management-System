<?php

namespace Tests\Feature\Events;

use App\Actions\Events\ArchiveEvent;
use App\Actions\Events\CancelEvent;
use App\Actions\Events\CompleteEvent;
use App\Actions\Events\MarkEventOngoing;
use App\Actions\Events\PublishEvent;
use App\Actions\Registrations\RegisterAttendee;
use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LifecycleTransitionsTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function makeOrgWithMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $org->memberships()->create([
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
        ]);
        session(['current_organization_id' => $org->id]);

        return [$user, $org];
    }

    private function assertTransitionRejected(Event $event, string $action, string $expectedStatus, string $errorKey = 'lifecycle'): void
    {
        $activityCount = ActivityLog::count();

        try {
            app($action)->handle($event->refresh(), $event->organization->memberships()->first()->user);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($errorKey, $e->errors());
        }

        $this->assertSame($expectedStatus, $event->refresh()->status->value);
        $this->assertSame($activityCount, ActivityLog::count());
    }

    // ─── Valid transitions ──────────────────────────────────────────────────

    public function test_draft_can_be_cancelled_before_publication(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->draft()->create();

        (app(CancelEvent::class))->handle($event, $owner);

        $event->refresh();

        $this->assertSame(EventStatus::Cancelled, $event->status);
        $this->assertNotNull($event->cancelled_at);

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'actor_user_id' => $owner->id,
            'action' => 'event.cancelled',
            'subject_type' => 'event',
            'subject_id' => $event->id,
        ]);
        $audit = ActivityLog::where('action', 'event.cancelled')->first();
        $this->assertSame('draft', $audit->properties['previous_status']);
        $this->assertSame('cancelled', $audit->properties['new_status']);
    }

    public function test_published_event_starts_and_tracks_started_at(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->published()->create();

        (app(MarkEventOngoing::class))->handle($event, $owner);

        $event->refresh();

        $this->assertSame(EventStatus::Ongoing, $event->status);
        $this->assertNotNull($event->started_at);

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $org->id,
            'actor_user_id' => $owner->id,
            'action' => 'event.started',
            'subject_type' => 'event',
        ]);
    }

    public function test_published_event_can_be_cancelled(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->published()->create();

        (app(CancelEvent::class))->handle($event, $owner);

        $this->assertSame(EventStatus::Cancelled, $event->refresh()->status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'event.cancelled', 'event_id' => $event->id]);
    }

    public function test_ongoing_event_completes_and_tracks_completed_at(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->ongoing()->create();

        (app(CompleteEvent::class))->handle($event, $owner);

        $event->refresh();

        $this->assertSame(EventStatus::Completed, $event->status);
        $this->assertNotNull($event->completed_at);

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $org->id,
            'actor_user_id' => $owner->id,
            'action' => 'event.completed',
            'subject_type' => 'event',
        ]);
    }

    public function test_ongoing_event_can_be_cancelled_as_interrupted(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->ongoing()->create();

        (app(CancelEvent::class))->handle($event, $owner);

        $this->assertSame(EventStatus::Cancelled, $event->refresh()->status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'event.cancelled', 'event_id' => $event->id]);
    }

    public function test_completed_event_can_be_archived(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->completed()->create();

        (app(ArchiveEvent::class))->handle($event, $owner);

        $event->refresh();

        $this->assertSame(EventStatus::Archived, $event->status);
        $this->assertNotNull($event->archived_at);

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $org->id,
            'actor_user_id' => $owner->id,
            'action' => 'event.archived',
            'subject_type' => 'event',
        ]);
        $audit = ActivityLog::where('action', 'event.archived')->first();
        $this->assertSame('completed', $audit->properties['previous_status']);
        $this->assertSame('archived', $audit->properties['new_status']);
    }

    public function test_cancelled_event_can_be_archived(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->cancelled()->create();

        (app(ArchiveEvent::class))->handle($event, $owner);

        $this->assertSame(EventStatus::Archived, $event->refresh()->status);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'event.archived',
            'event_id' => $event->id,
        ]);
    }

    // ─── Invalid transitions (BUSINESS_FLOW §4.1.2) ────────────────────────

    public function test_archived_event_cannot_go_ongoing(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->archived()->create();

        $this->assertTransitionRejected($event, MarkEventOngoing::class, EventStatus::Archived->value);
    }

    public function test_completed_event_cannot_go_ongoing(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->completed()->create();

        $this->assertTransitionRejected($event, MarkEventOngoing::class, EventStatus::Completed->value);
    }

    public function test_cancelled_event_cannot_go_ongoing(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->cancelled()->create();

        $this->assertTransitionRejected($event, MarkEventOngoing::class, EventStatus::Cancelled->value);
    }

    public function test_cancelled_event_cannot_be_published(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->cancelled()->create();

        $this->assertTransitionRejected($event, PublishEvent::class, EventStatus::Cancelled->value, 'publish');
    }

    public function test_published_event_cannot_be_completed_directly(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->published()->create();

        $this->assertTransitionRejected($event, CompleteEvent::class, EventStatus::Published->value);
    }

    public function test_published_event_cannot_be_archived(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->published()->create();

        $this->assertTransitionRejected($event, ArchiveEvent::class, EventStatus::Published->value);
    }

    public function test_draft_event_cannot_be_archived(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->draft()->create();

        $this->assertTransitionRejected($event, ArchiveEvent::class, EventStatus::Draft->value);
    }

    public function test_completed_event_cannot_be_cancelled(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->completed()->create();

        $this->assertTransitionRejected($event, CancelEvent::class, EventStatus::Completed->value);
    }

    public function test_archived_event_cannot_be_cancelled(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->archived()->create();

        $this->assertTransitionRejected($event, CancelEvent::class, EventStatus::Archived->value);
    }

    public function test_draft_event_cannot_be_completed(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->draft()->create();

        $this->assertTransitionRejected($event, CompleteEvent::class, EventStatus::Draft->value);
    }

    // ─── Cancel blocks new registrations (EBR-003) ──────────────────────────

    public function test_cancelled_event_blocks_new_registrations(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->published()->create([
            'registration_enabled' => true,
        ]);

        (app(CancelEvent::class))->handle($event, $owner);

        $this->expectException(ValidationException::class);

        app(RegisterAttendee::class)->handle($event->refresh(), [
            'attendee_name' => 'Jane Doe',
            'attendee_email' => 'jane@example.com',
        ]);

        $this->assertDatabaseCount('registrations', 0);
    }
}
