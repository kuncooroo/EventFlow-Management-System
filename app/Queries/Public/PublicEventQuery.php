<?php

namespace App\Queries\Public;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PublicEventQuery
{
    /**
     * Resolve a publicly accessible event by its slug.
     *
     * Only statuses reachable after publication are exposed. Draft and archived
     * events are never rendered on the public page.
     */
    public function resolve(string $slug): ?Event
    {
        return Event::query()
            ->where('public_slug', $slug)
            ->whereIn('status', [
                EventStatus::Published->value,
                EventStatus::Ongoing->value,
                EventStatus::Completed->value,
                EventStatus::Cancelled->value,
            ])
            ->first();
    }

    /**
     * Ticket types currently offered to attendees, ordered for display.
     */
    public function availableTicketTypes(Event $event): Collection
    {
        return $event->ticketTypes()
            ->active()
            ->where(function (Builder $query) {
                $query->whereNull('available_from')->orWhere('available_from', '<=', now());
            })
            ->where(function (Builder $query) {
                $query->whereNull('available_until')->orWhere('available_until', '>=', now());
            })
            ->ordered()
            ->get();
    }

    /**
     * Number of capacity-consuming registrations.
     *
     * Guarded by a schema check because the registrations table is introduced by
     * the registration task; until then every event has zero registrations.
     */
    public function registrationCount(Event $event): int
    {
        if (! Schema::hasTable('registrations')) {
            return 0;
        }

        return (int) DB::table('registrations')
            ->where('event_id', $event->id)
            ->where('status', 'confirmed')
            ->count();
    }

    /**
     * @return array{state: string, label: string, tone: string, note: string}
     */
    public function ctaState(Event $event): array
    {
        $timezone = $event->organization?->timezone ?? 'UTC';

        if ($event->status === EventStatus::Cancelled) {
            return [
                'state' => 'cancelled',
                'label' => 'This event has been cancelled.',
                'tone' => 'red',
                'note' => 'Registration is closed for this event.',
            ];
        }

        if (! $event->registration_enabled) {
            return [
                'state' => 'closed',
                'label' => 'Registration Closed',
                'tone' => 'slate',
                'note' => 'Registration is disabled for this event.',
            ];
        }

        $startsAt = $event->registration_starts_at;
        $endsAt = $event->registration_ends_at;
        $now = now();

        if ($startsAt !== null && $now->lt($startsAt)) {
            return [
                'state' => 'scheduled',
                'label' => 'Registration opens on '.$startsAt->copy()->setTimezone($timezone)->format('M j, Y g:i A'),
                'tone' => 'amber',
                'note' => 'Registration is not open yet.',
            ];
        }

        if ($endsAt !== null && $now->gt($endsAt)) {
            return [
                'state' => 'closed',
                'label' => 'Registration Closed',
                'tone' => 'slate',
                'note' => 'Registration ended on '.$endsAt->copy()->setTimezone($timezone)->format('M j, Y g:i A').'.',
            ];
        }

        if ($event->capacity !== null && $this->registrationCount($event) >= $event->capacity) {
            return [
                'state' => 'sold-out',
                'label' => 'Sold Out',
                'tone' => 'slate',
                'note' => 'All spots for this event are taken.',
            ];
        }

        return [
            'state' => 'open',
            'label' => 'Register Now',
            'tone' => 'teal',
            'note' => 'Registration is open for this event.',
        ];
    }
}
