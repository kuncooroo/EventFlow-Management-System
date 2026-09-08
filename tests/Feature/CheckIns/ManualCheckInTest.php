<?php

namespace Tests\Feature\CheckIns;

use App\Actions\CheckIns\CheckInAttendee;
use App\Enums\CheckInMethod;
use App\Enums\CheckInOutcome;
use App\Enums\OrganizationRole;
use App\Enums\RegistrationStatus;
use App\Livewire\Attendees\CheckInAction;
use App\Livewire\CheckIn\ManualCheckInSearch;
use App\Models\ActivityLog;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

class ManualCheckInTest extends TestCase
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

    private function checkIn(Event $event, Registration $registration, User $operator)
    {
        return app(CheckInAttendee::class)->handle($event, $registration, $operator, CheckInMethod::Manual);
    }

    // ─── Happy path ────────────────────────────────────────────────────────

    public function test_owner_manually_checks_in_confirmed_registration_with_audit(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->confirmed()->create();

        $result = $this->checkIn($event, $registration, $owner);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(CheckInOutcome::Success, $result->outcome);
        $this->assertSame(1, CheckIn::count());
        $this->assertSame($registration->id, $result->checkIn->registration_id);
        $this->assertSame($owner->id, $result->checkIn->operator_user_id);
        $this->assertSame(CheckInMethod::Manual, $result->checkIn->method);
        $this->assertNull($result->checkIn->ticket_id);
        $this->assertNotNull($result->checkIn->checked_in_at);
        $this->assertSame(RegistrationStatus::Confirmed, $registration->fresh()->status);

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'actor_user_id' => $owner->id,
            'actor_label' => $owner->name,
            'action' => 'checkin.manual',
            'subject_type' => 'registration',
            'subject_id' => $registration->id,
        ]);

        $audit = ActivityLog::where('subject_id', $registration->id)
            ->where('action', 'checkin.manual')
            ->first();

        $this->assertSame('manual', $audit->properties['method']);
        $this->assertNotNull($audit->properties['checked_in_at']);
    }

    // ─── Duplicate ─────────────────────────────────────────────────────────

    public function test_duplicate_manual_check_in_does_not_create_second_row(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->confirmed()->create();
        CheckIn::factory()->for($registration)->create(['operator_user_id' => $owner->id]);

        $result = $this->checkIn($event, $registration, $owner);

        $this->assertSame(CheckInOutcome::Duplicate, $result->outcome);
        $this->assertSame(1, DB::table('check_ins')->where('registration_id', $registration->id)->count());
        $this->assertSame($registration->id, $result->previousCheckIn->registration_id);

        $auditCount = ActivityLog::where('action', 'checkin.manual')->count();
        $this->assertSame(0, $auditCount);
    }

    public function test_registration_unique_index_blocks_raw_duplicate_insert(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->confirmed()->create();

        DB::table('check_ins')->insert([
            'registration_id' => $registration->id,
            'operator_user_id' => $owner->id,
            'method' => 'manual',
            'checked_in_at' => now(),
            'created_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('check_ins')->insert([
            'registration_id' => $registration->id,
            'operator_user_id' => $owner->id,
            'method' => 'manual',
            'checked_in_at' => now()->addMinute(),
            'created_at' => now(),
        ]);
    }

    public function test_two_sequential_attempts_produce_one_success_and_one_duplicate(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->confirmed()->create();

        $first = $this->checkIn($event, $registration, $owner);
        $second = $this->checkIn($event, $registration, $owner);

        $this->assertTrue($first->isSuccess());
        $this->assertSame(CheckInOutcome::Duplicate, $second->outcome);
        $this->assertSame(1, DB::table('check_ins')->where('registration_id', $registration->id)->count());
    }

    public function test_concurrent_duplicate_check_in_race_creates_exactly_one_row(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->confirmed()->create();
        $eventId = $event->id;
        $registrationId = $registration->id;
        $operatorId = $owner->id;

        // Commit the wrapping test transaction so worker processes observe the rows.
        DB::connection()->commit();

        $attempt = static function () use ($org, $eventId, $registrationId, $operatorId): string {
            // Concurrent workers are fresh processes without the parent's session
            // context, so restore the organization context before authorizing.
            Session::put('current_organization_id', $org->id);

            return app(CheckInAttendee::class)
                ->handle(
                    Event::findOrFail($eventId),
                    Registration::findOrFail($registrationId),
                    User::findOrFail($operatorId),
                    CheckInMethod::Manual,
                )
                ->outcome->value;
        };

        $outcomes = Concurrency::run([$attempt, $attempt]);

        $this->assertSame(1, collect($outcomes)->filter(fn ($o) => $o === CheckInOutcome::Success->value)->count());
        $this->assertSame(1, collect($outcomes)->filter(fn ($o) => $o === CheckInOutcome::Duplicate->value)->count());
        $this->assertSame(1, DB::table('check_ins')->where('registration_id', $registrationId)->count());
    }

    // ─── Ineligible ────────────────────────────────────────────────────────

    public function test_cancelled_registration_cannot_be_checked_in(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->cancelled()->create();

        $result = $this->checkIn($event, $registration, $owner);

        $this->assertSame(CheckInOutcome::Invalid, $result->outcome);
        $this->assertSame(0, DB::table('check_ins')->where('registration_id', $registration->id)->count());
        $this->assertDatabaseMissing('activity_logs', ['action' => 'checkin.manual']);
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

    public function test_assigned_staff_can_check_in(): void
    {
        [$staff, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::Staff);
        $event = Event::factory()->for($org)->create();

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        $registration = Registration::factory()->for($event)->confirmed()->create();

        $result = $this->checkIn($event, $registration, $staff);

        $this->assertTrue($result->isSuccess());
        $this->assertSame($staff->id, $result->checkIn->operator_user_id);
    }

    public function test_unassigned_staff_cannot_check_in(): void
    {
        [$staff, $org] = $this->makeOrgWithMember(OrganizationRole::Staff);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->confirmed()->create();

        $this->actingAs($staff)
            ->get(route('app.events.check-in.index', $event))
            ->assertForbidden();
    }

    public function test_cross_org_event_check_in_is_forbidden(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $otherEvent = Event::factory()->for($otherOrg)->create();

        $this->actingAs($owner)
            ->get(route('app.events.check-in.index', $otherEvent))
            ->assertNotFound();
    }

    public function test_check_in_action_throws_for_unauthorized_operator(): void
    {
        [$viewer, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::Viewer);
        $event = Event::factory()->for($org)->create();

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        $registration = Registration::factory()->for($event)->confirmed()->create();

        $this->expectException(AuthorizationException::class);

        $this->checkIn($event, $registration, $viewer);
    }

    // ─── Livewire ──────────────────────────────────────────────────────────

    public function test_manual_search_page_checks_in_attendee_via_livewire(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->confirmed()->create(['attendee_name' => 'Sari Dewi']);

        Livewire::actingAs($owner)
            ->test(ManualCheckInSearch::class, ['event' => $event])
            ->set('search', 'Sari')
            ->assertSee('Sari Dewi')
            ->assertSee('Check In')
            ->call('checkIn', $registration->id)
            ->assertSee('Checked In');

        $this->assertSame(1, DB::table('check_ins')->where('registration_id', $registration->id)->count());
    }

    public function test_detail_page_shows_check_in_button_and_checks_in(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $registration = Registration::factory()->for($event)->confirmed()->create();

        Livewire::actingAs($owner)
            ->test(CheckInAction::class, ['event' => $event, 'registration' => $registration])
            ->assertSee('Check In')
            ->call('checkIn')
            ->assertRedirect(route('app.events.attendees.show', [$event, $registration]));

        $this->assertSame(1, DB::table('check_ins')->where('registration_id', $registration->id)->count());
    }

    public function test_detail_page_does_not_show_check_in_button_for_viewer(): void
    {
        [$viewer, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::Viewer);
        $event = Event::factory()->for($org)->create();

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        $registration = Registration::factory()->for($event)->confirmed()->create();

        Livewire::actingAs($viewer)
            ->test(CheckInAction::class, ['event' => $event, 'registration' => $registration])
            ->assertDontSee('Check In')
            ->assertSee('Check-in permission required');
    }
}
