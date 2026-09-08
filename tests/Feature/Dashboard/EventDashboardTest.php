<?php

namespace Tests\Feature\Dashboard;

use App\Enums\OrganizationRole;
use App\Livewire\Reports\EventDashboard;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\User;
use App\Queries\Dashboard\EventDashboardQuery;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventDashboardTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function seedEventWithCheckins(): array
    {
        [, $org, $event] = $this->makeOrgEvent();

        $general = TicketType::factory()->for($event)->capped(100)->create(['name' => 'General']);
        $student = TicketType::factory()->for($event)->capped(50)->create(['name' => 'Student']);

        foreach ([$general, $general, $student, $student] as $i => $type) {
            $confirmed = $i < 4;
            $registration = Registration::factory()
                ->for($event)
                ->for($type, 'ticketType')
                ->{$confirmed ? 'confirmed' : 'cancelled'}()
                ->create();

            $this->checkInIf($registration, in_array($i, [0, 1, 2], true));
        }

        // One cancelled General registration.
        Registration::factory()
            ->for($event)
            ->for($general, 'ticketType')
            ->cancelled()
            ->create();

        return [$org, $event];
    }

    private function makeOrgEvent(): array
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $membership = $org->memberships()->create([
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
            'joined_at' => now(),
        ]);
        $event = Event::factory()->for($org)->published()->create(['name' => 'Metrics Summit']);

        session([OrganizationContext::SESSION_KEY => $org->id]);

        return [$user, $org, $event, $membership];
    }

    private function checkInIf(Registration $registration, bool $shouldCheckIn): void
    {
        if ($shouldCheckIn) {
            CheckIn::factory()->for($registration)->create(['checked_in_at' => now()]);
        }
    }

    private function metrics(Event $event): array
    {
        return app(EventDashboardQuery::class)($event);
    }

    // ─── Reconciliation with known seed (FR-DSH-003..005) ───────────────────

    public function test_metrics_reconcile_with_seeded_registration_and_checkin_data(): void
    {
        [, $event] = $this->seedEventWithCheckins();

        $data = $this->metrics($event);

        $this->assertSame(5, $data['total_registrations']);
        $this->assertSame(4, $data['confirmed_registrations']);
        $this->assertSame(3, $data['checked_in']);
        $this->assertSame(75, $data['attendance_percentage']);
    }

    public function test_ticket_type_summary_matches_per_type_totals(): void
    {
        [, $event] = $this->seedEventWithCheckins();

        $types = $this->metrics($event)['ticket_types']->keyBy('name');

        $this->assertSame(3, $types['General']->registrations_count);
        $this->assertSame(2, $types['General']->confirmed_count);
        $this->assertSame(2, $types['General']->checked_in_count);

        $this->assertSame(2, $types['Student']->registrations_count);
        $this->assertSame(2, $types['Student']->confirmed_count);
        $this->assertSame(1, $types['Student']->checked_in_count);
    }

    // ─── Zero attendees is safe (DASH-003) ───────────────────────────────────

    public function test_zero_attendee_event_has_no_attendance_percentage_and_renders(): void
    {
        [$user, $org, $event] = $this->makeOrgEvent();

        $data = $this->metrics($event);

        $this->assertSame(0, $data['total_registrations']);
        $this->assertSame(0, $data['checked_in']);
        $this->assertNull($data['attendance_percentage']);

        Livewire::actingAs($user)
            ->test(EventDashboard::class, ['event' => $event])
            ->assertOk()
            ->assertSee('Not available')
            ->assertSee('0', false);
    }

    // ─── Recent lists are scoped and limited ─────────────────────────────────

    public function test_recent_registrations_are_scoped_and_limited_to_five(): void
    {
        [$user, $org, $event] = $this->makeOrgEvent();
        $type = TicketType::factory()->for($event)->create(['name' => 'General']);

        foreach (range(1, 6) as $i) {
            Registration::factory()
                ->for($event)
                ->for($type, 'ticketType')
                ->confirmed()
                ->create(['registered_at' => now()->addMinutes($i)]);
        }

        $recent = $this->metrics($event)['recent_registrations'];

        $this->assertCount(5, $recent);
        $this->assertTrue($recent->every(fn (Registration $r) => $r->event_id === $event->id));
    }

    public function test_recent_check_ins_are_scoped_to_the_event(): void
    {
        [, , $event] = $this->makeOrgEvent();
        [, , $otherEvent] = $this->makeOrgEvent();

        $type = TicketType::factory()->for($event)->create(['name' => 'General']);
        $otherType = TicketType::factory()->for($otherEvent)->create(['name' => 'Other']);

        $registration = Registration::factory()->for($event)->for($type, 'ticketType')->confirmed()->create();
        CheckIn::factory()->for($registration)->create(['checked_in_at' => now()]);

        $otherRegistration = Registration::factory()->for($otherEvent)->for($otherType, 'ticketType')->confirmed()->create();
        CheckIn::factory()->for($otherRegistration)->create(['checked_in_at' => now()]);

        $recent = $this->metrics($event)['recent_check_ins'];

        $this->assertCount(1, $recent);
        $this->assertSame($registration->id, $recent->first()->registration_id);
    }

    // ─── Authorization (FR-DSH-006, DASH-001) ───────────────────────────────

    public function test_assigned_viewer_can_view_dashboard_read_only(): void
    {
        [$viewer, $org, $event, $membership] = $this->makeOrgEvent();
        $membership->update(['role' => OrganizationRole::Viewer]);
        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        $this->actingAs($viewer)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.events.dashboard', $event))
            ->assertOk()
            ->assertSee($event->name)
            ->assertSee('Registrations');
    }

    public function test_unassigned_viewer_is_forbidden(): void
    {
        [$viewer, $org, $event] = $this->makeOrgEvent();
        $org->memberships()->where('user_id', $viewer->id)->update(['role' => OrganizationRole::Viewer]);

        $this->actingAs($viewer)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.events.dashboard', $event))
            ->assertForbidden();
    }

    public function test_event_from_another_organization_returns_not_found(): void
    {
        [$user, $userOrg] = $this->makeOrgEvent();
        $foreignEvent = Event::factory()->create(['name' => 'Foreign Event']);

        $this->actingAs($user)
            ->withSession([OrganizationContext::SESSION_KEY => $userOrg->id])
            ->get(route('app.events.dashboard', $foreignEvent))
            ->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        [, , $event] = $this->makeOrgEvent();

        $this->get(route('app.events.dashboard', $event))
            ->assertRedirect(route('login'));
    }

    // ─── Page rendering ──────────────────────────────────────────────────────

    public function test_dashboard_page_renders_for_owner(): void
    {
        [$owner, $org, $event] = $this->makeOrgEvent();
        $type = TicketType::factory()->for($event)->capped(100)->create(['name' => 'General']);
        Registration::factory()->for($event)->for($type, 'ticketType')->confirmed()->create();

        $this->actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.events.dashboard', $event))
            ->assertOk()
            ->assertSee($event->name)
            ->assertSee('General')
            ->assertSee('Ticket Types')
            ->assertSee('Recent Registrations')
            ->assertSee('Recent Check-Ins');
    }
}
