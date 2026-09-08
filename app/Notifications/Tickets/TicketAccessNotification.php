<?php

namespace App\Notifications\Tickets;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

class TicketAccessNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
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
        $ticket = $registration->ticket;

        $message = (new MailMessage)
            ->subject(__('Your Ticket for :event', ['event' => $event->name]))
            ->greeting(__('Hello :name,', ['name' => $registration->attendee_name]))
            ->line(__('You have successfully registered for :event.', ['event' => $event->name]))
            ->line(__('Your registration reference is: :reference', ['reference' => $registration->registration_code]))
            ->line(__('Your ticket code is: :code', ['code' => $ticket->ticket_code]));

        if ($event->start_at !== null) {
            $message->line(__('The event starts on :start.', ['start' => $this->formatEventStart($event)]));
        }

        return $message
            ->action(__('View Your Ticket'), route('tickets.public.show', ['token' => $ticket->ticket_code]))
            ->line(__('This ticket link is personal to you and shows your ticket for check-in.'));
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
