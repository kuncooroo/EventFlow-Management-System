<?php

namespace Tests\Feature\Events;

use App\Actions\Events\PublishEvent;
use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Livewire\Events\EventSetup;
use App\Livewire\Events\PublishEventButton;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PublishEventTest extends TestCase
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

    // ─── Action: success ────────────────────────────────────────────────────

    public function test_ready_draft_publishes_with_slug_and_audit(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->publishReady()->create();

        (app(PublishEvent::class))->handle($event, $owner);

        $event->refresh();

        $this->assertSame(EventStatus::Published->value, $event->status->value);
        $this->assertNotNull($event->published_at);
        $this->assertNotNull($event->public_slug);

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'actor_user_id' => $owner->id,
            'action' => 'event.published',
            'subject_type' => 'event',
            'subject_id' => $event->id,
        ]);
    }

    public function test_publish_generates_unique_slug_on_collision(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        Event::factory()->for($org)->published()->create(['name' => 'Ready Event', 'public_slug' => 'ready-event']);
        $event = Event::factory()->for($org)->publishReady()->create(['name' => 'Ready Event']);

        (app(PublishEvent::class))->handle($event, $owner);

        $event->refresh();
        $this->assertSame('ready-event-2', $event->public_slug);
    }

    // ─── Action: not ready ──────────────────────────────────────────────────

    public function test_unready_draft_is_rejected_without_changing_status(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create([
            'status' => EventStatus::Draft,
            'name' => '',
            'organizer_name' => null,
            'start_at' => null,
            'end_at' => null,
        ]);

        try {
            (app(PublishEvent::class))->handle($event, $owner);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('publish', $e->errors());
        }

        $event->refresh();
        $this->assertSame(EventStatus::Draft->value, $event->status->value);
        $this->assertNull($event->published_at);
        $this->assertSame(0, ActivityLog::count());
    }

    public function test_missing_organizer_blocks_publication(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->publishReady()->create(['organizer_name' => null]);

        try {
            (app(PublishEvent::class))->handle($event, $owner);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('organizer', $e->errors()['publish'][0]);
        }

        $this->assertSame(EventStatus::Draft->value, $event->refresh()->status->value);
    }

    // ─── Action: invalid transition ─────────────────────────────────────────

    public function test_non_draft_event_cannot_be_published(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->published()->create(['name' => 'Already Published']);

        try {
            (app(PublishEvent::class))->handle($event, $owner);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Draft', $e->errors()['publish'][0]);
        }

        $this->assertSame(EventStatus::Published->value, $event->refresh()->status->value);
    }

    // ─── Authorization ──────────────────────────────────────────────────────

    public function test_assigned_event_manager_can_publish(): void
    {
        [$manager, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::EventManager);
        $event = Event::factory()->for($org)->publishReady()->create();

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        (app(PublishEvent::class))->handle($event, $manager);

        $this->assertSame(EventStatus::Published->value, $event->refresh()->status->value);
    }

    public function test_unassigned_event_manager_cannot_publish(): void
    {
        [$manager, $org] = $this->makeOrgWithMember(OrganizationRole::EventManager);
        $event = Event::factory()->for($org)->publishReady()->create();

        $this->expectException(AuthorizationException::class);

        (app(PublishEvent::class))->handle($event, $manager);
    }

    public function test_staff_cannot_publish(): void
    {
        [$staff, $org] = $this->makeOrgWithMember(OrganizationRole::Staff);
        $event = Event::factory()->for($org)->publishReady()->create();

        $this->expectException(AuthorizationException::class);

        (app(PublishEvent::class))->handle($event, $staff);
    }

    public function test_other_organization_owner_cannot_publish(): void
    {
        [$otherOwner] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $event = Event::factory()->for($otherOrg)->publishReady()->create();

        $this->expectException(AuthorizationException::class);

        (app(PublishEvent::class))->handle($event, $otherOwner);
    }

    // ─── Livewire UI ────────────────────────────────────────────────────────

    public function test_publish_button_publishes_ready_event(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->publishReady()->create();

        Livewire::actingAs($owner)
            ->test(PublishEventButton::class, ['event' => $event])
            ->call('publish');

        $this->assertSame(EventStatus::Published->value, $event->refresh()->status->value);
    }

    public function test_publish_button_shows_readiness_errors_for_unready_event(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create([
            'status' => EventStatus::Draft,
            'name' => '',
            'organizer_name' => null,
            'start_at' => null,
            'end_at' => null,
        ]);

        Livewire::actingAs($owner)
            ->test(PublishEventButton::class, ['event' => $event])
            ->assertSee('Not ready to publish')
            ->assertSee('An event name is required');

        $this->assertSame(EventStatus::Draft->value, $event->refresh()->status->value);
    }

    public function test_setup_page_shows_publish_section(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->publishReady()->create();

        Livewire::actingAs($owner)
            ->test(EventSetup::class, ['event' => $event])
            ->assertSee('Publish Event');
    }
}
