<?php

namespace App\Queries\Dashboard;

use App\Enums\RegistrationStatus;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Registration;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EventDashboardQuery
{
    /**
     * Per-event dashboard metrics derived from authoritative registration,
     * ticket, and check-in records (SFR-DSH-001, FR-DSH-003..005).
     *
     * @return array{
     *     total_registrations: int,
     *     confirmed_registrations: int,
     *     checked_in: int,
     *     attendance_percentage: int|null,
     *     ticket_types: Collection<int, TicketType>,
     *     recent_registrations: Collection<int, Registration>,
     *     recent_check_ins: Collection<int, CheckIn>,
     * }
     */
    public function __invoke(Event $event): array
    {
        $registrations = Registration::query()->where('event_id', $event->id);

        $confirmedRegistrations = (clone $registrations)
            ->where('status', RegistrationStatus::Confirmed->value);

        $checkedIn = CheckIn::query()->whereIn(
            'registration_id',
            (clone $registrations)->select('id'),
        );

        $confirmedTotal = $confirmedRegistrations->count();
        $checkedInTotal = $checkedIn->count();

        return [
            'total_registrations' => (clone $registrations)->count(),
            'confirmed_registrations' => $confirmedTotal,
            'checked_in' => $checkedInTotal,
            'attendance_percentage' => $confirmedTotal > 0
                ? (int) round(($checkedInTotal / $confirmedTotal) * 100)
                : null,
            'ticket_types' => $this->ticketTypeSummary($event),
            'recent_registrations' => Registration::query()
                ->where('event_id', $event->id)
                ->with('ticketType')
                ->orderByDesc('registered_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'recent_check_ins' => $this->recentCheckIns($event),
        ];
    }

    /**
     * Ticket-type summary, aggregated without N+1 (FR-RPT-003).
     *
     * @return Collection<int, TicketType>
     */
    private function ticketTypeSummary(Event $event): Collection
    {
        return TicketType::query()
            ->where('event_id', $event->id)
            ->ordered()
            ->addSelect([
                'registrations_count' => Registration::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('registrations.ticket_type_id', 'ticket_types.id'),
                'confirmed_count' => Registration::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('registrations.ticket_type_id', 'ticket_types.id')
                    ->where('status', RegistrationStatus::Confirmed->value),
                'checked_in_count' => CheckIn::query()
                    ->join('registrations as r', 'r.id', '=', 'check_ins.registration_id')
                    ->selectRaw('count(*)')
                    ->whereColumn('r.ticket_type_id', 'ticket_types.id'),
            ])
            ->get();
    }

    /**
     * Most recent check-ins for the event, limited to five.
     *
     * @return Collection<int, CheckIn>
     */
    private function recentCheckIns(Event $event): Collection
    {
        return CheckIn::query()
            ->whereHas(
                'registration',
                fn (Builder $registration) => $registration->where('event_id', $event->id),
            )
            ->with(['registration.ticketType'])
            ->orderByDesc('checked_in_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }
}
