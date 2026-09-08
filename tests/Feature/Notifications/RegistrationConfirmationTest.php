<?php

namespace Tests\Feature\Notifications;

use App\Actions\Registrations\RegisterAttendee;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\TicketType;
use App\Notifications\Registrations\RegistrationConfirmationNotification;
use App\Notifications\Tickets\TicketAccessNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationConfirmationTest extends TestCase
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

    // ─── Non-ticketed registrations get the confirmation email ────────────

    public function test_non_ticketed_registration_sends_confirmation_to_attendee(): void
    {
        Notification::fake();

        $event = $this->makeOpenEvent();
        $this->registerViaAction($event, [
            'attendee_name' => 'Anisa Putri',
            'attendee_email' => 'anisa@test.com',
        ]);

        Notification::assertSentTo(
            Notification::route('mail', 'anisa@test.com'),
            RegistrationConfirmationNotification::class
        );
        Notification::assertNotSentTo(
            Notification::route('mail', 'anisa@test.com'),
            TicketAccessNotification::class
        );
    }

    public function test_confirmation_email_contains_event_name_and_reference(): void
    {
        Notification::fake();

        $event = $this->makeOpenEvent(['organizer_name' => 'OpenTech']);
        $registration = $this->registerViaAction($event, [
            'attendee_name' => 'Anisa Putri',
            'attendee_email' => 'anisa@test.com',
        ]);

        $mail = (new RegistrationConfirmationNotification($registration))->toMail(new AnonymousNotifiable);
        $html = $mail->render();

        $this->assertStringContainsString($event->name, $html);
        $this->assertStringContainsString($registration->registration_code, $html);
        $this->assertStringContainsString($event->organizer_name, $html);
    }

    public function test_queued_job_defers_confirmation_until_after_commit(): void
    {
        $notification = new RegistrationConfirmationNotification(Registration::factory()->make());

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertInstanceOf(ShouldQueueAfterCommit::class, $notification);

        $job = new SendQueuedNotifications(
            Notification::route('mail', 'anisa@test.com'),
            $notification
        );

        $this->assertTrue($job->afterCommit);
    }

    // ─── Ticketed registrations get the unified ticket-access email ────────

    public function test_ticketed_registration_sends_only_ticket_access_email(): void
    {
        Notification::fake();

        $event = $this->makeOpenEvent();
        $ticketType = $this->makeTicketType($event, ['capacity' => 100]);
        $registration = $this->registerViaAction($event, [
            'attendee_name' => 'Anisa Putri',
            'attendee_email' => 'anisa@test.com',
            'ticket_type_id' => $ticketType->id,
        ]);

        $this->assertNotNull($registration->ticket);

        Notification::assertSentTo(
            Notification::route('mail', 'anisa@test.com'),
            TicketAccessNotification::class
        );
        Notification::assertNotSentTo(
            Notification::route('mail', 'anisa@test.com'),
            RegistrationConfirmationNotification::class
        );
    }

    public function test_ticket_access_email_links_only_attendees_own_ticket(): void
    {
        Notification::fake();

        $event = $this->makeOpenEvent();
        $ticketType = $this->makeTicketType($event, ['capacity' => 100]);
        $registration = $this->registerViaAction($event, [
            'attendee_name' => 'Anisa Putri',
            'attendee_email' => 'anisa@test.com',
            'ticket_type_id' => $ticketType->id,
        ]);

        $ticket = $registration->ticket;
        $expectedUrl = route('tickets.public.show', ['token' => $ticket->ticket_code]);

        $mail = (new TicketAccessNotification($registration))->toMail(new AnonymousNotifiable);
        $html = $mail->render();

        $this->assertStringContainsString($event->name, $html);
        $this->assertStringContainsString($registration->registration_code, $html);
        $this->assertStringContainsString($ticket->ticket_code, $html);
        $this->assertStringContainsString($expectedUrl, $html);
        $this->assertStringNotContainsString('some-other-token', $html);
    }

    // ─── Mail failure must never invalidate the registration ──────────────

    public function test_registration_stays_confirmed_when_mail_job_fails(): void
    {
        Notification::fake();

        $event = $this->makeOpenEvent();
        $registration = $this->registerViaAction($event, [
            'attendee_name' => 'Anisa Putri',
            'attendee_email' => 'anisa@test.com',
        ]);

        $this->assertSame(RegistrationStatus::Confirmed, $registration->status);

        $job = new SendQueuedNotifications(
            Notification::route('mail', 'anisa@test.com'),
            new RegistrationConfirmationNotification($registration)
        );

        $job->failed(new \RuntimeException('mail transport down'));

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'status' => RegistrationStatus::Confirmed->value,
            'attendee_email' => 'anisa@test.com',
        ]);
    }
}
