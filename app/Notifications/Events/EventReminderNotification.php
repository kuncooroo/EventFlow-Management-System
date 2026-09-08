<?php

namespace App\Notifications\Events;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

class EventReminderNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        public readonly Registration $registration,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $registration = $this->registration;
        $event = $registration->event;

        $message = (new MailMessage)
            ->subject(__('Reminder: :event is coming up soon', ['event' => $event->name]))
            ->greeting(__('Hello :name,', ['name' => $registration->attendee_name]))
            ->line(__('This is a reminder that :event is coming up soon.', ['event' => $event->name]));

        if ($event->start_at !== null) {
            $message->line(__('The event starts on :start.', ['start' => $this->formatEventStart($event)]));
        }

        if ($event->organizer_name !== null) {
            $message->line(__('Organizer: :organizer', ['organizer' => $event->organizer_name]));
        }

        return $message
            ->line(__('Your registration reference is: :reference', ['reference' => $registration->registration_code]))
            ->action(__('View Your Registration'), $this->registrationUrl($registration, $event));
    }

    private function registrationUrl(Registration $registration, Event $event): string
    {
        if ($registration->ticket !== null) {
            return route('tickets.public.show', ['token' => $registration->ticket->ticket_code]);
        }

        if ($event->public_slug !== null) {
            return route('public.events.show', $event->public_slug);
        }

        return route('home');
    }

    private function formatEventStart(Event $event): string
    {
        $timezone = $event->organization?->timezone ?? config('app.timezone');

        return $event->start_at->setTimezone($timezone)->format('D, M j, Y g:i A');
    }

    public function failed(Throwable $e): void
    {
        report($e);
    }
}
