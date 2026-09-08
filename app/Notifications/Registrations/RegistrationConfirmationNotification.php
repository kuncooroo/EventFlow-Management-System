<?php

namespace App\Notifications\Registrations;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

class RegistrationConfirmationNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
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
        $event = $this->registration->event;

        return (new MailMessage)
            ->subject(__('Registration Confirmed – :event', ['event' => $event->name]))
            ->greeting(__('Hello :name,', ['name' => $this->registration->attendee_name]))
            ->line(__('You have successfully registered for :event.', ['event' => $event->name]))
            ->line(__('Your registration reference is: :reference', ['reference' => $this->registration->registration_code]))
            ->line(__('Organizer: :organizer', ['organizer' => $event->organizer_name]))
            ->line(__('We look forward to seeing you at the event.'));
    }

    public function failed(Throwable $e): void
    {
        report($e);
    }
}
