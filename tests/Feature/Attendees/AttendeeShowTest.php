<?php

namespace Tests\Feature\Attendees;

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\RegistrationAnswer;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendeeShowTest extends TestCase
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

    // ─── Access ─────────────────────────────────────────────────────────────

    public function test_owner_can_view_attendee_detail_with_registration_sections(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['name' => 'Detail Event']);
        $ticketType = TicketType::factory()->free()->for($event)->create(['name' => 'VIP']);
        $registration = Registration::factory()
            ->for($event)
            ->for($ticketType, 'ticketType')
            ->create([
                'attendee_name' => 'Dina Nova',
                'attendee_email' => 'dina@example.com',
                'registration_code' => 'REG-ABC-123',
                'registered_at' => now()->subDays(2),
            ]);
        RegistrationAnswer::factory()->for($registration)->create([
            'field_label_snapshot' => 'T-Shirt Size',
            'answer_text' => 'Large',
        ]);

        $this->actingAs($owner)
            ->get(route('app.events.attendees.show', [$event, $registration]))
            ->assertOk()
            ->assertSee('Dina Nova')
            ->assertSee('dina@example.com')
            ->assertSee('REG-ABC-123')
            ->assertSee('VIP')
            ->assertSee('T-Shirt Size')
            ->assertSee('Large')
            ->assertSee('Not checked in');
    }

    public function test_detail_links_to_public_ticket_when_ticket_exists(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->create();
        $ticket = Ticket::factory()->for($registration)->create();

        $this->actingAs($owner)
            ->get(route('app.events.attendees.show', [$event, $registration]))
            ->assertOk()
            ->assertSee(route('tickets.public.show', $ticket->ticket_code))
            ->assertSee($ticket->ticket_code);
    }

    public function test_detail_omits_ticket_section_when_no_ticket_exists(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->create();

        $this->actingAs($owner)
            ->get(route('app.events.attendees.show', [$event, $registration]))
            ->assertOk()
            ->assertDontSee('View Ticket');
    }

    public function test_assigned_viewer_can_view_attendee_detail(): void
    {
        [$viewer, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::Viewer);
        $event = Event::factory()->for($org)->create();

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        $registration = Registration::factory()->for($event)->create(['attendee_name' => 'Viewer Joe']);

        $this->actingAs($viewer)
            ->get(route('app.events.attendees.show', [$event, $registration]))
            ->assertOk()
            ->assertSee('Viewer Joe');
    }

    public function test_unassigned_staff_cannot_view_attendee_detail(): void
    {
        [$staff, $org] = $this->makeOrgWithMember(OrganizationRole::Staff);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->create();

        $this->actingAs($staff)
            ->get(route('app.events.attendees.show', [$event, $registration]))
            ->assertForbidden();
    }

    public function test_cross_org_attendee_detail_is_blocked(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $otherEvent = Event::factory()->for($otherOrg)->create();
        $otherRegistration = Registration::factory()->for($otherEvent)->create();

        $this->actingAs($owner)
            ->get(route('app.events.attendees.show', [$otherEvent, $otherRegistration]))
            ->assertNotFound();
    }

    public function test_registration_from_another_event_is_rejected(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $eventA = Event::factory()->for($org)->create(['name' => 'Event A']);
        $eventB = Event::factory()->for($org)->create(['name' => 'Event B']);
        $registrationB = Registration::factory()->for($eventB)->create();

        $this->actingAs($owner)
            ->get(route('app.events.attendees.show', [$eventA, $registrationB]))
            ->assertNotFound();
    }

    public function test_viewer_does_not_see_cancel_action_on_detail(): void
    {
        [$viewer, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::Viewer);
        $event = Event::factory()->for($org)->create();

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        $registration = Registration::factory()->for($event)->create();

        $this->actingAs($viewer)
            ->get(route('app.events.attendees.show', [$event, $registration]))
            ->assertOk()
            ->assertDontSee('Cancel Registration')
            ->assertSee('Cancel permission required');
    }

    public function test_owner_sees_cancel_action_on_detail(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->create();

        $this->actingAs($owner)
            ->get(route('app.events.attendees.show', [$event, $registration]))
            ->assertOk()
            ->assertSee('Cancel Registration');
    }
}
