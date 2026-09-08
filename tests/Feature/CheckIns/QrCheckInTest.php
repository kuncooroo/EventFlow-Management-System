<?php

namespace Tests\Feature\CheckIns;

use App\Actions\CheckIns\CheckInAttendee;
use App\Actions\CheckIns\ResolveTicketForCheckIn;
use App\Enums\CheckInMethod;
use App\Enums\CheckInOutcome;
use App\Enums\OrganizationRole;
use App\Enums\RegistrationStatus;
use App\Livewire\CheckIn\CheckInScanner;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class QrCheckInTest extends TestCase
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

    private function makeTicketedRegistration(Event $event): array
    {
        $ticketType = TicketType::factory()->free()->for($event)->create();
        $registration = Registration::factory()->for($event)->for($ticketType, 'ticketType')->confirmed()->create();
        $ticket = Ticket::factory()->for($registration)->create();

        return [$registration, $ticket];
    }

    private function scanQr(Event $event, Ticket $ticket, User $operator)
    {
        $resolved = app(ResolveTicketForCheckIn::class)->handle($event, $ticket->qr_token);

        $this->assertNotNull($resolved);

        return app(CheckInAttendee::class)->handle($event, $resolved->registration, $operator, CheckInMethod::Qr, $resolved);
    }

    // ─── Happy path ────────────────────────────────────────────────────────

    public function test_valid_qr_token_checks_in_once_with_audit(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        [$registration, $ticket] = $this->makeTicketedRegistration($event);

        $result = $this->scanQr($event, $ticket, $owner);

        $this->assertTrue($result->isSuccess());
        $this->assertSame($registration->id, $result->checkIn->registration_id);
        $this->assertSame($ticket->id, $result->checkIn->ticket_id);
        $this->assertSame(CheckInMethod::Qr, $result->checkIn->method);

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'actor_user_id' => $owner->id,
            'action' => 'checkin.qr',
            'subject_type' => 'registration',
            'subject_id' => $registration->id,
        ]);

        $audit = ActivityLog::where('action', 'checkin.qr')->first();
        $this->assertSame('qr', $audit->properties['method']);
        $this->assertSame($ticket->id, $audit->properties['ticket_id']);
        $this->assertArrayNotHasKey('qr_token', $audit->properties);
    }

    // ─── Duplicate / invalid ───────────────────────────────────────────────

    public function test_duplicate_qr_shows_previous_check_in_and_creates_no_row(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        [$registration, $ticket] = $this->makeTicketedRegistration($event);

        $first = $this->scanQr($event, $ticket, $owner);
        $second = $this->scanQr($event, $ticket, $owner);

        $this->assertTrue($first->isSuccess());
        $this->assertSame(CheckInOutcome::Duplicate, $second->outcome);
        $this->assertSame($first->checkIn->id, $second->previousCheckIn->id);
        $this->assertSame(1, DB::table('check_ins')->where('registration_id', $registration->id)->count());
    }

    public function test_unknown_qr_is_invalid_and_creates_no_row(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->confirmed()->create();

        Livewire::actingAs($owner)
            ->test(CheckInScanner::class, ['event' => $event])
            ->set('qrToken', str_repeat('a', 64))
            ->call('attempt')
            ->assertSee('cannot be used for this event');

        $this->assertSame(0, DB::table('check_ins')->where('registration_id', $registration->id)->count());
    }

    public function test_wrong_event_qr_is_invalid_and_creates_no_row(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $eventA = Event::factory()->for($org)->create(['name' => 'Event A']);
        $eventB = Event::factory()->for($org)->create(['name' => 'Event B']);
        [, $ticketB] = $this->makeTicketedRegistration($eventB);

        $this->assertNull(app(ResolveTicketForCheckIn::class)->handle($eventA, $ticketB->qr_token));

        Livewire::actingAs($owner)
            ->test(CheckInScanner::class, ['event' => $eventA])
            ->set('qrToken', $ticketB->qr_token)
            ->call('attempt')
            ->assertSee('cannot be used for this event');

        $this->assertSame(0, DB::table('check_ins')->count());
        $this->assertDatabaseMissing('activity_logs', ['action' => 'checkin.qr']);
    }

    public function test_cancelled_registration_qr_is_invalid(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $ticketType = TicketType::factory()->free()->for($event)->create();
        $registration = Registration::factory()->for($event)->for($ticketType, 'ticketType')->cancelled()->create();
        $ticket = Ticket::factory()->for($registration)->create();

        $resolved = app(ResolveTicketForCheckIn::class)->handle($event, $ticket->qr_token);

        $this->assertNotNull($resolved);
        $this->assertSame(RegistrationStatus::Cancelled, $resolved->registration->status);

        $result = app(CheckInAttendee::class)->handle($event, $resolved->registration, $owner, CheckInMethod::Qr, $resolved);

        $this->assertSame(CheckInOutcome::Invalid, $result->outcome);
        $this->assertSame(0, DB::table('check_ins')->count());
        $this->assertDatabaseMissing('activity_logs', ['action' => 'checkin.qr']);
    }

    // ─── Authorization ─────────────────────────────────────────────────────

    public function test_unassigned_viewer_cannot_open_check_in_page(): void
    {
        [$viewer, $org] = $this->makeOrgWithMember(OrganizationRole::Viewer);
        $event = Event::factory()->for($org)->create();

        $this->actingAs($viewer)
            ->get(route('app.events.check-in.index', $event))
            ->assertForbidden();
    }

    // ─── Livewire / fallbacks ──────────────────────────────────────────────

    public function test_manual_fallback_is_always_available_on_check_in_page(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        $this->actingAs($owner)
            ->get(route('app.events.check-in.index', $event))
            ->assertOk()
            ->assertSee('Manual Check-In')
            ->assertSee('Entry code or pasted QR value');
    }

    public function test_typed_token_path_checks_in(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        [$registration, $ticket] = $this->makeTicketedRegistration($event);

        Livewire::actingAs($owner)
            ->test(CheckInScanner::class, ['event' => $event])
            ->set('qrToken', $ticket->qr_token)
            ->call('attempt')
            ->assertSee('Checked In')
            ->assertSee($registration->attendee_name);

        $this->assertSame(1, DB::table('check_ins')->where('registration_id', $registration->id)->count());
    }

    public function test_scan_attempts_are_rate_limited(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        [$registration, $ticket] = $this->makeTicketedRegistration($event);

        $key = 'checkin-scan:'.$event->id.':'.$owner->id;

        for ($i = 0; $i < 120; $i++) {
            RateLimiter::hit($key);
        }

        Livewire::actingAs($owner)
            ->test(CheckInScanner::class, ['event' => $event])
            ->set('qrToken', $ticket->qr_token)
            ->call('attempt')
            ->assertSee('Too many scans');

        $this->assertSame(0, DB::table('check_ins')->where('registration_id', $registration->id)->count());
    }
}
