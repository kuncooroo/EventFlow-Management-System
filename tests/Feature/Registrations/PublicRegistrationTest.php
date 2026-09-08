<?php

namespace Tests\Feature\Registrations;

use App\Actions\Registrations\RegisterAttendee;
use App\Enums\EventStatus;
use App\Enums\RegistrationFieldType;
use App\Events\RegistrationConfirmed;
use App\Livewire\Public\RegistrationForm;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\RegistrationField;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PublicRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function makeOpenEvent(array $overrides = []): Event
    {
        $org = Organization::factory()->create();

        return Event::factory()->for($org)->published()->create(array_merge([
            'organizer_name' => 'OpenTech',
            'registration_enabled' => true,
            'registration_starts_at' => now()->subDay(),
            'registration_ends_at' => now()->addDays(30),
        ], $overrides));
    }

    private function makeTicketType(Event $event, array $overrides = []): TicketType
    {
        return TicketType::factory()->free()->for($event)->create(array_merge([
            'available_from' => now()->subDay(),
            'available_until' => now()->addDays(30),
        ], $overrides));
    }

    private function registerViaAction(Event $event, array $data): Registration
    {
        return app(RegisterAttendee::class)->handle($event, $data);
    }

    // ─── Happy path ────────────────────────────────────────────────────────

    public function test_valid_registration_creates_exactly_one_confirmed_registration(): void
    {
        $event = $this->makeOpenEvent(['capacity' => 10]);

        $registration = $this->registerViaAction($event, [
            'attendee_name' => 'Anisa Putri',
            'attendee_email' => 'anisa@test.com',
            'attendee_phone' => null,
            'attendee_organization' => null,
            'ticket_type_id' => null,
        ]);

        $this->assertSame(1, Registration::where('event_id', $event->id)->count());
        $this->assertSame('confirmed', $registration->status->value);
        $this->assertSame('Confirmed', $registration->status->label());
        $this->assertSame($event->id, $registration->event_id);
        $this->assertNotNull($registration->registration_code);
        $this->assertSame(26, strlen($registration->registration_code));
        $this->assertNotNull($registration->registered_at);
    }

    public function test_livewire_form_happy_path_shows_reference(): void
    {
        $event = $this->makeOpenEvent(['capacity' => 10]);

        $component = Livewire::test(RegistrationForm::class, ['slug' => $event->public_slug])
            ->set('attendee_name', 'Budi Santoso')
            ->set('attendee_email', 'budi@test.com')
            ->call('submit');

        $registration = Registration::where('event_id', $event->id)->first();

        $this->assertSame(1, Registration::where('event_id', $event->id)->count());
        $component->assertSet('submitted', true)
            ->assertSee($registration->registration_code);
    }

    public function test_registration_with_ticket_type_issues_ticket(): void
    {
        $event = $this->makeOpenEvent();
        $ticket = $this->makeTicketType($event, ['capacity' => 100]);

        $registration = $this->registerViaAction($event, [
            'attendee_name' => 'Anisa Putri',
            'attendee_email' => 'anisa@test.com',
            'ticket_type_id' => $ticket->id,
        ]);

        $this->assertNotEmpty($registration->ticket);
        $this->assertSame($ticket->id, $registration->ticket_type_id);
        $this->assertSame(26, strlen($registration->ticket->ticket_code));
        $this->assertSame(64, strlen($registration->ticket->qr_token));
        $this->assertNotNull($registration->ticket->issued_at);
    }

    public function test_free_event_without_ticket_types_does_not_issue_ticket(): void
    {
        $event = $this->makeOpenEvent();

        $registration = $this->registerViaAction($event, [
            'attendee_name' => 'Anisa Putri',
            'attendee_email' => 'anisa@test.com',
        ]);

        $this->assertNull($registration->ticket);
        $this->assertNull($registration->ticket_type_id);
    }

    public function test_custom_answers_are_stored_with_snapshots(): void
    {
        $event = $this->makeOpenEvent();
        $field = RegistrationField::factory()->active()->for($event)->create([
            'label' => 'T-Shirt Size',
            'field_type' => RegistrationFieldType::Text,
            'is_required' => true,
        ]);

        $registration = $this->registerViaAction($event, [
            'attendee_name' => 'Anisa Putri',
            'attendee_email' => 'anisa@test.com',
            'answers' => [$field->id => 'Large'],
        ]);

        $answer = $registration->answers()->first();

        $this->assertSame('T-Shirt Size', $answer->field_label_snapshot);
        $this->assertSame('text', $answer->field_type_snapshot);
        $this->assertSame('Large', $answer->answer_text);
        $this->assertNull($answer->answer_json);
    }

    // ─── Validation failures create none ───────────────────────────────────

    public function test_missing_name_creates_none(): void
    {
        $event = $this->makeOpenEvent();

        $component = Livewire::test(RegistrationForm::class, ['slug' => $event->public_slug])
            ->set('attendee_email', 'anisa@test.com')
            ->call('submit');

        $component->assertHasErrors(['attendee_name' => 'required']);
        $this->assertSame(0, Registration::count());
    }

    public function test_invalid_email_creates_none(): void
    {
        $event = $this->makeOpenEvent();

        $component = Livewire::test(RegistrationForm::class, ['slug' => $event->public_slug])
            ->set('attendee_name', 'Anisa')
            ->set('attendee_email', 'not-an-email')
            ->call('submit');

        $component->assertHasErrors(['attendee_email' => 'email']);
        $this->assertSame(0, Registration::count());
    }

    public function test_missing_required_custom_field_creates_none(): void
    {
        $event = $this->makeOpenEvent();
        RegistrationField::factory()->active()->required()->for($event)->create([
            'label' => 'Institution',
            'field_type' => RegistrationFieldType::Text,
        ]);

        $component = Livewire::test(RegistrationForm::class, ['slug' => $event->public_slug])
            ->set('attendee_name', 'Anisa')
            ->set('attendee_email', 'anisa@test.com')
            ->call('submit');

        $field = $event->registrationFields()->first();
        $component->assertHasErrors(['answers.'.$field->id => 'required']);
        $this->assertSame(0, Registration::count());
    }

    public function test_required_phone_flag_enforced(): void
    {
        $event = $this->makeOpenEvent(['require_phone' => true]);

        $component = Livewire::test(RegistrationForm::class, ['slug' => $event->public_slug])
            ->set('attendee_name', 'Anisa')
            ->set('attendee_email', 'anisa@test.com')
            ->call('submit');

        $component->assertHasErrors(['attendee_phone' => 'required']);
        $this->assertSame(0, Registration::count());
    }

    public function test_required_organization_flag_enforced(): void
    {
        $event = $this->makeOpenEvent(['require_organization' => true]);

        $component = Livewire::test(RegistrationForm::class, ['slug' => $event->public_slug])
            ->set('attendee_name', 'Anisa')
            ->set('attendee_email', 'anisa@test.com')
            ->call('submit');

        $component->assertHasErrors(['attendee_organization' => 'required']);
        $this->assertSame(0, Registration::count());
    }

    public function test_ticket_type_required_when_ticket_types_exist(): void
    {
        $event = $this->makeOpenEvent();
        $this->makeTicketType($event);

        $component = Livewire::test(RegistrationForm::class, ['slug' => $event->public_slug])
            ->set('attendee_name', 'Anisa')
            ->set('attendee_email', 'anisa@test.com')
            ->call('submit');

        $component->assertHasErrors(['ticket_type_id' => 'required']);
        $this->assertSame(0, Registration::count());
    }

    // ─── Unsuitable events create none ─────────────────────────────────────

    public function test_draft_event_returns_404(): void
    {
        $event = Event::factory()->create([
            'public_slug' => 'draft-event',
            'status' => EventStatus::Draft,
        ]);

        $this->get(route('public.events.register', $event->public_slug))->assertNotFound();
        $this->assertSame(0, Registration::count());
    }

    public function test_cancelled_event_creates_none(): void
    {
        $event = $this->makeOpenEvent(['status' => EventStatus::Cancelled]);

        $component = Livewire::test(RegistrationForm::class, ['slug' => $event->public_slug])
            ->set('attendee_name', 'Anisa')
            ->set('attendee_email', 'anisa@test.com')
            ->call('submit');

        $this->assertSame(0, Registration::count());
        $component->assertHasErrors(['capacity']);
    }

    public function test_disabled_registration_creates_none(): void
    {
        $event = $this->makeOpenEvent(['registration_enabled' => false]);

        $component = Livewire::test(RegistrationForm::class, ['slug' => $event->public_slug])
            ->set('attendee_name', 'Anisa')
            ->set('attendee_email', 'anisa@test.com')
            ->call('submit');

        $this->assertSame(0, Registration::count());
        $component->assertHasErrors(['capacity']);
    }

    public function test_registration_window_not_started_creates_none(): void
    {
        $event = $this->makeOpenEvent([
            'registration_starts_at' => now()->addDay(),
            'registration_ends_at' => now()->addDays(30),
        ]);

        $component = Livewire::test(RegistrationForm::class, ['slug' => $event->public_slug])
            ->set('attendee_name', 'Anisa')
            ->set('attendee_email', 'anisa@test.com')
            ->call('submit');

        $this->assertSame(0, Registration::count());
        $component->assertHasErrors(['capacity']);
    }

    public function test_registration_window_ended_creates_none(): void
    {
        $event = $this->makeOpenEvent([
            'registration_starts_at' => now()->subDays(10),
            'registration_ends_at' => now()->subDay(),
        ]);

        $component = Livewire::test(RegistrationForm::class, ['slug' => $event->public_slug])
            ->set('attendee_name', 'Anisa')
            ->set('attendee_email', 'anisa@test.com')
            ->call('submit');

        $this->assertSame(0, Registration::count());
        $component->assertHasErrors(['capacity']);
    }

    // ─── Sold out ──────────────────────────────────────────────────────────

    public function test_full_event_creates_none(): void
    {
        $event = $this->makeOpenEvent(['capacity' => 1]);

        $this->registerViaAction($event, [
            'attendee_name' => 'Anisa',
            'attendee_email' => 'anisa@test.com',
        ]);

        $component = Livewire::test(RegistrationForm::class, ['slug' => $event->public_slug])
            ->set('attendee_name', 'Budi')
            ->set('attendee_email', 'budi@test.com')
            ->call('submit');

        $this->assertSame(1, Registration::where('event_id', $event->id)->count());
        $component->assertHasErrors(['capacity']);
    }

    public function test_full_ticket_type_creates_none(): void
    {
        $event = $this->makeOpenEvent(['capacity' => 10]);
        $ticket = $this->makeTicketType($event, ['capacity' => 1]);

        $this->registerViaAction($event, [
            'attendee_name' => 'Anisa',
            'attendee_email' => 'anisa@test.com',
            'ticket_type_id' => $ticket->id,
        ]);

        $component = Livewire::test(RegistrationForm::class, ['slug' => $event->public_slug])
            ->set('attendee_name', 'Budi')
            ->set('attendee_email', 'budi@test.com')
            ->set('ticket_type_id', $ticket->id)
            ->call('submit');

        $this->assertSame(1, Registration::where('event_id', $event->id)->count());
        $component->assertHasErrors(['ticket_type_id']);
    }

    public function test_cancelled_registration_releases_capacity(): void
    {
        $event = $this->makeOpenEvent(['capacity' => 1]);

        $first = $this->registerViaAction($event, [
            'attendee_name' => 'Anisa',
            'attendee_email' => 'anisa@test.com',
        ]);

        $first->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $second = $this->registerViaAction($event, [
            'attendee_name' => 'Budi',
            'attendee_email' => 'budi@test.com',
        ]);

        $this->assertSame(2, Registration::where('event_id', $event->id)->count());
        $this->assertNotNull($second);
    }

    public function test_ticket_type_availability_window_enforced(): void
    {
        $event = $this->makeOpenEvent();
        $ticket = $this->makeTicketType($event, [
            'available_from' => now()->addDay(),
            'available_until' => now()->addDays(30),
        ]);

        $this->expectException(ValidationException::class);

        $this->registerViaAction($event, [
            'attendee_name' => 'Anisa',
            'attendee_email' => 'anisa@test.com',
            'ticket_type_id' => $ticket->id,
        ]);
    }

    public function test_ticket_availability_window_over_enforced(): void
    {
        $event = $this->makeOpenEvent();
        $ticket = $this->makeTicketType($event, [
            'available_from' => now()->subDays(10),
            'available_until' => now()->subDay(),
        ]);

        $this->expectException(ValidationException::class);

        $this->registerViaAction($event, [
            'attendee_name' => 'Anisa',
            'attendee_email' => 'anisa@test.com',
            'ticket_type_id' => $ticket->id,
        ]);
    }

    public function test_inactive_ticket_type_rejected(): void
    {
        $event = $this->makeOpenEvent();
        $ticket = $this->makeTicketType($event, ['is_active' => false]);

        $this->expectException(ValidationException::class);

        $this->registerViaAction($event, [
            'attendee_name' => 'Anisa',
            'attendee_email' => 'anisa@test.com',
            'ticket_type_id' => $ticket->id,
        ]);
    }

    // ─── After-commit dispatch ─────────────────────────────────────────────

    public function test_registration_confirmed_dispatched_after_commit(): void
    {
        EventFacade::fake();
        $event = $this->makeOpenEvent();

        $registration = $this->registerViaAction($event, [
            'attendee_name' => 'Anisa',
            'attendee_email' => 'anisa@test.com',
        ]);

        EventFacade::assertDispatched(RegistrationConfirmed::class, function ($event) use ($registration) {
            return $event->registration->id === $registration->id;
        });
    }

    // ─── Concurrency ───────────────────────────────────────────────────────

    public function test_concurrent_final_seat_race_accepts_exactly_one(): void
    {
        $event = $this->makeOpenEvent(['capacity' => 1]);
        $eventId = $event->id;

        // Commit the wrapping test transaction so worker processes observe the row.
        DB::connection()->commit();

        $attempt = static function (int $id, string $name, string $email): bool {
            try {
                app(RegisterAttendee::class)->handle(
                    Event::findOrFail($id),
                    [
                        'attendee_name' => $name,
                        'attendee_email' => $email,
                    ]
                );

                return true;
            } catch (ValidationException) {
                return false;
            }
        };

        $results = Concurrency::run([
            fn () => $attempt($eventId, 'Anisa', 'anisa@test.com'),
            fn () => $attempt($eventId, 'Budi', 'budi@test.com'),
        ]);

        $winnerCount = collect($results)->filter()->count();

        $this->assertSame(1, $winnerCount);
        $this->assertSame(1, Registration::where('event_id', $eventId)->count());
    }
}
