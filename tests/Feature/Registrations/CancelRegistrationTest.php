<?php

namespace Tests\Feature\Registrations;

use App\Actions\Registrations\CancelRegistration;
use App\Enums\OrganizationRole;
use App\Enums\RegistrationStatus;
use App\Livewire\Attendees\CancelRegistrationAction;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\User;
use App\Services\Registrations\RegistrationCapacityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CancelRegistrationTest extends TestCase
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

    private function cancel(Registration $registration, Event $event, User $actor): Registration
    {
        return app(CancelRegistration::class)->handle($event, $registration, $actor);
    }

    // ─── Happy path ────────────────────────────────────────────────────────

    public function test_owner_can_cancel_confirmed_registration_with_audit(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->confirmed()->create();

        $result = $this->cancel($registration, $event, $owner);

        $this->assertSame(RegistrationStatus::Cancelled, $result->status);
        $this->assertNotNull($result->cancelled_at);
        $this->assertSame($owner->id, $result->cancelled_by_user_id);

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'actor_user_id' => $owner->id,
            'actor_label' => $owner->name,
            'action' => 'registration.status_changed',
            'subject_type' => 'registration',
            'subject_id' => $registration->id,
        ]);

        $audit = ActivityLog::where('subject_type', 'registration')
            ->where('subject_id', $registration->id)
            ->first();

        $this->assertStringContainsString('confirmed to cancelled', $audit->summary);
        $this->assertSame('confirmed', $audit->properties['previous_status']);
        $this->assertSame('cancelled', $audit->properties['new_status']);
    }

    public function test_cancellation_releases_event_capacity(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['capacity' => 1]);
        $registration = Registration::factory()->for($event)->confirmed()->create();

        $this->assertTrue(app(RegistrationCapacityService::class)->isEventSoldOut($event));

        $this->cancel($registration, $event, $owner);

        $this->assertFalse(app(RegistrationCapacityService::class)->isEventSoldOut($event));
        $this->assertSame(1, app(RegistrationCapacityService::class)->remainingEventCapacity($event));
        $this->assertSame(0, app(RegistrationCapacityService::class)->confirmedCount($event));
    }

    public function test_cancellation_releases_ticket_type_capacity(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $ticketType = TicketType::factory()->free()->for($event)->capped(1)->create();
        $registration = Registration::factory()->for($event)->for($ticketType, 'ticketType')->confirmed()->create();

        $this->assertTrue(app(RegistrationCapacityService::class)->isTicketTypeSoldOut($event, $ticketType));

        $this->cancel($registration, $event, $owner);

        $this->assertFalse(app(RegistrationCapacityService::class)->isTicketTypeSoldOut($event, $ticketType));
    }

    // ─── Invalid transitions ───────────────────────────────────────────────

    public function test_cancelling_already_cancelled_registration_is_rejected(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->cancelled()->create();

        try {
            $this->cancel($registration, $event, $owner);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('registration', $e->errors());
        }

        $fresh = $registration->fresh();
        $this->assertSame(RegistrationStatus::Cancelled, $fresh->status);
        $this->assertNull($fresh->cancelled_by_user_id);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'registration.status_changed']);
    }

    public function test_cancelled_registration_detail_shows_cancelled_state(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->confirmed()->create();

        $this->cancel($registration, $event, $owner);

        $this->actingAs($owner)
            ->get(route('app.events.attendees.show', [$event, $registration->fresh()]))
            ->assertOk()
            ->assertSee('Cancelled')
            ->assertSee('Cancelled At')
            ->assertSee('Cancelled By')
            ->assertSee('Registration is already cancelled');
    }

    // ─── Authorization ──────────────────────────────────────────────────────

    public function test_viewer_cannot_cancel_registration(): void
    {
        [$viewer, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::Viewer);
        $event = Event::factory()->for($org)->create();

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        $registration = Registration::factory()->for($event)->confirmed()->create();

        $this->expectException(AuthorizationException::class);

        $this->cancel($registration, $event, $viewer);
    }

    public function test_staff_cannot_cancel_registration(): void
    {
        [$staff, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::Staff);

        $event = Event::factory()->for($org)->create();

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        $registration = Registration::factory()->for($event)->confirmed()->create();

        $this->expectException(AuthorizationException::class);

        $this->cancel($registration, $event, $staff);
    }

    public function test_cross_org_registration_cannot_be_cancelled(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $otherEvent = Event::factory()->for($otherOrg)->create();
        $otherRegistration = Registration::factory()->for($otherEvent)->confirmed()->create();

        $this->expectException(AuthorizationException::class);

        $this->cancel($otherRegistration, $otherEvent, $owner);
    }

    public function test_cross_event_registration_cannot_be_cancelled_through_another_event(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $eventA = Event::factory()->for($org)->create(['name' => 'Event A']);
        $eventB = Event::factory()->for($org)->create(['name' => 'Event B']);
        $registrationB = Registration::factory()->for($eventB)->confirmed()->create();

        $this->expectException(AuthorizationException::class);

        $this->cancel($registrationB, $eventA, $owner);
    }

    // ─── Livewire component ─────────────────────────────────────────────────

    public function test_owner_can_cancel_via_livewire_component(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->confirmed()->create();

        Livewire::actingAs($owner)
            ->test(CancelRegistrationAction::class, ['event' => $event, 'registration' => $registration])
            ->call('cancel')
            ->assertRedirect(route('app.events.attendees.show', [$event, $registration]));

        $this->assertSame(
            RegistrationStatus::Cancelled,
            Registration::find($registration->id)->status,
        );
    }

    public function test_viewer_component_renders_but_does_not_cancel(): void
    {
        [$viewer, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::Viewer);
        $event = Event::factory()->for($org)->create();

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        $registration = Registration::factory()->for($event)->confirmed()->create();

        Livewire::actingAs($viewer)
            ->test(CancelRegistrationAction::class, ['event' => $event, 'registration' => $registration])
            ->assertSee('Cancel permission required')
            ->assertDontSee('Cancel Registration');

        $this->assertSame(RegistrationStatus::Confirmed, $registration->fresh()->status);
    }
}
