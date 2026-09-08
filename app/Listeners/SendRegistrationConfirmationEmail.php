<?php

namespace App\Listeners;

use App\Events\RegistrationConfirmed;
use App\Notifications\Registrations\RegistrationConfirmationNotification;
use App\Notifications\Tickets\TicketAccessNotification;
use Illuminate\Support\Facades\Notification;

class SendRegistrationConfirmationEmail
{
    /**
     * Send the attendee a single confirmation email that also carries ticket
     * access when a ticket was issued (FR-NOT-002, FR-NOT-003).
     */
    public function handle(RegistrationConfirmed $event): void
    {
        $registration = $event->registration;

        $notification = $registration->ticket !== null
            ? new TicketAccessNotification($registration)
            : new RegistrationConfirmationNotification($registration);

        Notification::route('mail', $registration->attendee_email)->notify($notification);
    }
}
