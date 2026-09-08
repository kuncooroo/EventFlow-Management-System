<?php

namespace Tests\Feature\Notifications;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Jobs\Notifications\SendEventReminderJob;
use App\Models\Event;
use App\Models\EventReminderSend;
use App\Models\Organization;
use App\Models\Registration;
use App\Notifications\Events\EventReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendEventRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    private function makeDueEvent(array $overrides = []): Event
    {
        $org = Organization::factory()->create();
        $startAt = now()->addHours(12);

        return Event::factory()->for($org)->published()->create(array_merge([
            'start_at' => $startAt,
            'reminder_enabled' => true,
            'reminder_hours_before' => 24,
        ], $overrides));
    }

    private function makeConfirmedRegistration(Event $event, array $overrides = []): Registration
    {
        return Registration::factory()->for($event)->create(array_merge([
            'attendee_name' => 'Anisa Putri',
            'attendee_email' => 'anisa@test.com',
        ], $overrides));
    }

    private function assertOnDemandSent(string $email): void
    {
        Notification::assertSentOnDemand(
            EventReminderNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $email
        );
    }

    // ─── Eligible send ──────────────────────────────────────────────────────

    public function test_sends_reminder_to_eligible_confirmed_attendees(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 08:00:00'));

        Notification::fake();

        $event = $this->makeDueEvent();
        $this->makeConfirmedRegistration($event);
        $this->makeConfirmedRegistration($event, [
            'attendee_name' => 'Rahmat Hidayat',
            'attendee_email' => 'rahmat@test.com',
        ]);

        $this->artisan('send:event-reminders')->assertSuccessful();

        Notification::assertSentTimes(EventReminderNotification::class, 2);
        $this->assertOnDemandSent('anisa@test.com');
        $this->assertOnDemandSent('rahmat@test.com');

        $this->assertDatabaseHas('event_reminder_sends', [
            'event_id' => $event->id,
        ]);
    }

    public function test_does_not_send_before_the_reminder_window_opens(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 08:00:00'));

        Notification::fake();

        $startInFuture = now()->addHours(120);
        $event = Event::factory()->published()->create([
            'start_at' => $startInFuture,
            'reminder_enabled' => true,
            'reminder_hours_before' => 24,
        ]);
        $this->makeConfirmedRegistration($event);

        $this->artisan('send:event-reminders')->assertSuccessful();

        Notification::assertNothingSent();

        $this->assertDatabaseMissing('event_reminder_sends', [
            'event_id' => $event->id,
        ]);
    }

    public function test_does_not_send_when_reminder_is_disabled(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 08:00:00'));

        Notification::fake();

        $event = $this->makeDueEvent(['reminder_enabled' => false]);
        $this->makeConfirmedRegistration($event);

        $this->artisan('send:event-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_send_when_offset_is_missing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 08:00:00'));

        Notification::fake();

        $event = $this->makeDueEvent(['reminder_hours_before' => null]);
        $this->makeConfirmedRegistration($event);

        $this->artisan('send:event-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_send_after_the_event_has_started(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 08:00:00'));

        Notification::fake();

        $event = $this->makeDueEvent(['start_at' => now()->subHour()]);
        $this->makeConfirmedRegistration($event);

        $this->artisan('send:event-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    // ─── Idempotency ────────────────────────────────────────────────────────

    public function test_duplicate_scheduler_run_does_not_duplicate_the_occurrence(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 08:00:00'));

        Notification::fake();

        $event = $this->makeDueEvent();
        $this->makeConfirmedRegistration($event);

        $this->artisan('send:event-reminders')->assertSuccessful();
        $this->artisan('send:event-reminders')->assertSuccessful();

        Notification::assertSentTimes(EventReminderNotification::class, 1);
        $this->assertOnDemandSent('anisa@test.com');

        $this->assertSame(1, EventReminderSend::where('event_id', $event->id)->count());
    }

    public function test_rescheduled_event_start_gets_a_new_occurrence_marker(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 08:00:00'));

        Notification::fake();

        $event = $this->makeDueEvent(['start_at' => now()->addHours(12)]);
        $this->makeConfirmedRegistration($event);

        $this->artisan('send:event-reminders')->assertSuccessful();

        $event->update(['start_at' => Carbon::parse('2026-09-11 08:00:00')]);
        Carbon::setTestNow(Carbon::parse('2026-09-10 12:00:00'));

        $this->artisan('send:event-reminders')->assertSuccessful();

        $this->assertSame(2, EventReminderSend::where('event_id', $event->id)->count());
        Notification::assertSentTimes(EventReminderNotification::class, 2);
        $this->assertOnDemandSent('anisa@test.com');
    }

    public function test_command_reports_success_when_silent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 08:00:00'));

        Notification::fake();

        $this->artisan('send:event-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    // ─── Suppression ────────────────────────────────────────────────────────

    public function test_cancelled_event_suppresses_reminders(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 08:00:00'));

        Notification::fake();

        $event = $this->makeDueEvent(['status' => EventStatus::Cancelled]);
        $this->makeConfirmedRegistration($event);

        $this->artisan('send:event-reminders')->assertSuccessful();

        Notification::assertNothingSent();

        $this->assertDatabaseMissing('event_reminder_sends', [
            'event_id' => $event->id,
        ]);
    }

    public function test_completed_event_suppresses_reminders(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 08:00:00'));

        Notification::fake();

        $event = $this->makeDueEvent(['status' => EventStatus::Completed]);
        $this->makeConfirmedRegistration($event);

        $this->artisan('send:event-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_cancelled_registration_is_skipped_but_confirmed_one_is_kept(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 08:00:00'));

        Notification::fake();

        $event = $this->makeDueEvent();
        $this->makeConfirmedRegistration($event, [
            'attendee_name' => 'Rahmat Hidayat',
            'attendee_email' => 'rahmat@test.com',
        ]);
        $event->registrations()->create([
            'registration_code' => bin2hex(random_bytes(13)),
            'status' => RegistrationStatus::Cancelled,
            'attendee_name' => 'Budi Santoso',
            'attendee_email' => 'budi@test.com',
            'registered_at' => now(),
            'cancelled_at' => now(),
        ]);

        $this->artisan('send:event-reminders')->assertSuccessful();

        Notification::assertSentTimes(EventReminderNotification::class, 1);
        $this->assertOnDemandSent('rahmat@test.com');
    }

    public function test_no_registrations_still_marks_the_occurrence_as_handled(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 08:00:00'));

        Notification::fake();

        $event = $this->makeDueEvent();

        $this->artisan('send:event-reminders')->assertSuccessful();

        Notification::assertNothingSent();

        $this->assertSame(1, EventReminderSend::where('event_id', $event->id)->count());
    }

    public function test_reminder_job_runs_synchronously_for_confirmed_registrant(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 08:00:00'));

        Notification::fake();

        $event = $this->makeDueEvent();
        $registration = $this->makeConfirmedRegistration($event);

        SendEventReminderJob::dispatchSync($registration);

        Notification::assertSentTimes(EventReminderNotification::class, 1);
        $this->assertOnDemandSent('anisa@test.com');
    }
}
