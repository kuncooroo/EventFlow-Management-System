<?php

namespace Tests\Feature\Notifications;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Ticket;
use App\Notifications\Events\EventReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EventReminderNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeRegistration(array $overrides = []): Registration
    {
        $org = Organization::factory()->create(['timezone' => 'Asia/Jakarta']);
        $event = Event::factory()
            ->for($org)
            ->published()
            ->create([
                'name' => 'Tech Day 2026',
                'organizer_name' => 'OpenTech',
                'start_at' => now()->addDays(2),
                'public_slug' => 'tech-day-2026',
                'reminder_enabled' => true,
                'reminder_hours_before' => 24,
            ]);

        return Registration::factory()->for($event)->create(array_merge([
            'attendee_name' => 'Anisa Putri',
            'attendee_email' => 'anisa@test.com',
        ], $overrides));
    }

    public function test_notification_contracts_queue_until_after_commit(): void
    {
        $notification = new EventReminderNotification(Registration::factory()->make());

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertInstanceOf(ShouldQueueAfterCommit::class, $notification);

        $job = new SendQueuedNotifications(
            Notification::route('mail', 'anisa@test.com'),
            $notification
        );

        $this->assertTrue($job->afterCommit);
    }

    public function test_email_contains_event_and_attendee_details(): void
    {
        Notification::fake();

        $registration = $this->makeRegistration();

        $mail = (new EventReminderNotification($registration))->toMail(new AnonymousNotifiable);
        $html = $mail->render();

        $this->assertStringContainsString('Tech Day 2026', $html);
        $this->assertStringContainsString('OpenTech', $html);
        $this->assertStringContainsString('Anisa Putri', $html);
        $this->assertStringContainsString($registration->registration_code, $html);
        $this->assertStringContainsString(
            $registration->event->start_at->setTimezone('Asia/Jakarta')->format('D, M j, Y g:i A'),
            $html
        );
    }

    public function test_email_links_to_ticket_when_ticket_exists(): void
    {
        Notification::fake();

        $registration = $this->makeRegistration();
        $ticket = Ticket::factory()->for($registration)->create();

        $mail = (new EventReminderNotification($registration))->toMail(new AnonymousNotifiable);
        $html = $mail->render();

        $this->assertStringContainsString(
            route('tickets.public.show', ['token' => $ticket->ticket_code]),
            $html
        );
    }

    public function test_email_links_to_event_page_when_no_ticket(): void
    {
        Notification::fake();

        $registration = $this->makeRegistration();

        $mail = (new EventReminderNotification($registration))->toMail(new AnonymousNotifiable);
        $html = $mail->render();

        $this->assertStringContainsString(
            route('public.events.show', $registration->event->public_slug),
            $html
        );
    }

    public function test_email_never_leaks_another_attendees_data(): void
    {
        Notification::fake();

        $registration = $this->makeRegistration();
        $other = Registration::factory()->for($registration->event)->create([
            'attendee_name' => 'Rahmat Hidayat',
            'attendee_email' => 'rahmat@test.com',
        ]);

        $mail = (new EventReminderNotification($registration))->toMail(new AnonymousNotifiable);
        $html = $mail->render();

        $this->assertStringNotContainsString($other->attendee_email, $html);
        $this->assertStringNotContainsString($other->attendee_name, $html);
        $this->assertStringNotContainsString($other->registration_code, $html);
    }
}
