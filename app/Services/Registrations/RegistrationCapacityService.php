<?php

namespace App\Services\Registrations;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\TicketType;

class RegistrationCapacityService
{
    /**
     * Count capacity-consuming registrations for an event.
     */
    public function confirmedCount(Event $event): int
    {
        return $event->registrations()
            ->where('status', RegistrationStatus::Confirmed->value)
            ->count();
    }

    /**
     * Count capacity-consuming registrations for a specific ticket type.
     */
    public function confirmedCountForTicketType(Event $event, TicketType $ticketType): int
    {
        return $event->registrations()
            ->where('ticket_type_id', $ticketType->id)
            ->where('status', RegistrationStatus::Confirmed->value)
            ->count();
    }

    /**
     * Whether the event has no remaining capacity.
     */
    public function isEventSoldOut(Event $event): bool
    {
        if ($event->capacity === null) {
            return false;
        }

        return $this->confirmedCount($event) >= $event->capacity;
    }

    /**
     * Whether the ticket type has no remaining capacity.
     */
    public function isTicketTypeSoldOut(Event $event, TicketType $ticketType): bool
    {
        if ($ticketType->capacity === null) {
            return false;
        }

        return $this->confirmedCountForTicketType($event, $ticketType) >= $ticketType->capacity;
    }

    /**
     * Remaining event capacity, or null when uncapped.
     */
    public function remainingEventCapacity(Event $event): ?int
    {
        if ($event->capacity === null) {
            return null;
        }

        return max(0, $event->capacity - $this->confirmedCount($event));
    }

    /**
     * Remaining ticket type capacity, or null when uncapped.
     */
    public function remainingTicketTypeCapacity(Event $event, TicketType $ticketType): ?int
    {
        if ($ticketType->capacity === null) {
            return null;
        }

        return max(0, $ticketType->capacity - $this->confirmedCountForTicketType($event, $ticketType));
    }
}
