<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;

class RegistrationPolicy
{
    public function __construct(
        private EventPolicy $events
    ) {}

    public function viewAny(User $user, Event $event): bool
    {
        return $this->events->view($user, $event);
    }

    public function view(User $user, Registration $registration, Event $event): bool
    {
        if ($registration->event_id !== $event->id) {
            return false;
        }

        return $this->events->view($user, $registration->event);
    }

    public function cancel(User $user, Registration $registration, Event $event): bool
    {
        if ($registration->event_id !== $event->id) {
            return false;
        }

        return $this->events->cancelRegistration($user, $registration->event);
    }

    public function checkIn(User $user, Registration $registration, Event $event): bool
    {
        if ($registration->event_id !== $event->id) {
            return false;
        }

        return $this->events->checkIn($user, $registration->event);
    }
}
