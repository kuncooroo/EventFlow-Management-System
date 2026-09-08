<?php

namespace App\Queries\Reports;

use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use App\Queries\Reports\Concerns\ScopesToAccessibleEvents;
use Illuminate\Database\Eloquent\Builder;

class AttendanceReportQuery
{
    use ScopesToAccessibleEvents;

    /**
     * Attendance summary per event (or per ticket type when an event is
     * selected) for accessible events. Totals reconcile with the event
     * dashboard (FR-RPT-002, FR-RPT-004, DASH-004).
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     rows: list<array{
     *         event: string,
     *         ticket_type: string|null,
     *         registrations: int,
     *         confirmed: int,
     *         checked_in: int,
     *         attendance_percentage: int|null,
     *     }>,
     *     total_registrations: int,
     *     total_confirmed: int,
     *     total_checked_in: int,
     *     attendance_percentage: int|null,
     * }
     */
    public function __invoke(Organization $organization, User $user, array $filters = []): array
    {
        $ids = $this->accessibleEventIds($organization, $user);

        if ($ids === []) {
            return $this->emptyResult();
        }

        $eventId = $this->filteredEventId($ids, $filters['event_id'] ?? null);
        if ($eventId === false) {
            return $this->emptyResult();
        }

        $registrations = Registration::query()->whereIn('registrations.event_id', $ids);

        if ($eventId !== null) {
            $registrations->where('registrations.event_id', $eventId);
        }

        $this->applyDateRange(
            $registrations,
            (string) ($filters['registered_from'] ?? ''),
            (string) ($filters['registered_to'] ?? ''),
        );

        $grouped = (clone $registrations)->toBase()
            ->join('events', 'events.id', '=', 'registrations.event_id')
            ->leftJoin('ticket_types', 'ticket_types.id', '=', 'registrations.ticket_type_id')
            ->leftJoin('check_ins', 'check_ins.registration_id', '=', 'registrations.id')
            ->selectRaw(
                'registrations.event_id, events.name as event_name, ticket_types.name as ticket_type_name, '
                .'registrations.status, count(distinct registrations.id) as total, count(check_ins.id) as checked_in',
            )
            ->groupBy('registrations.event_id', 'events.name', 'ticket_types.name', 'registrations.status')
            ->get();

        $buckets = [];
        $overall = ['registrations' => 0, 'confirmed' => 0, 'checked_in' => 0];

        foreach ($grouped as $row) {
            $key = $eventId === null
                ? 'event:'.$row->event_id
                : 'type:'.($row->ticket_type_name ?? "\0");

            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'event' => $row->event_name,
                    'ticket_type' => $eventId === null ? null : ($row->ticket_type_name ?? 'No ticket type'),
                    'registrations' => 0,
                    'confirmed' => 0,
                    'checked_in' => 0,
                ];
            }

            $total = (int) $row->total;
            $checkedIn = (int) $row->checked_in;

            $buckets[$key]['registrations'] += $total;
            $buckets[$key]['checked_in'] += $checkedIn;
            $overall['registrations'] += $total;
            $overall['checked_in'] += $checkedIn;

            if ($row->status === 'confirmed') {
                $buckets[$key]['confirmed'] += $total;
                $overall['confirmed'] += $total;
            }
        }

        $rows = array_map(
            fn (array $bucket): array => [
                'event' => $bucket['event'],
                'ticket_type' => $bucket['ticket_type'],
                'registrations' => $bucket['registrations'],
                'confirmed' => $bucket['confirmed'],
                'checked_in' => $bucket['checked_in'],
                'attendance_percentage' => $this->percentage($bucket['checked_in'], $bucket['confirmed']),
            ],
            array_values($buckets),
        );

        usort($rows, fn (array $a, array $b): int => $b['confirmed'] <=> $a['confirmed']);

        return [
            'rows' => $rows,
            'total_registrations' => $overall['registrations'],
            'total_confirmed' => $overall['confirmed'],
            'total_checked_in' => $overall['checked_in'],
            'attendance_percentage' => $this->percentage($overall['checked_in'], $overall['confirmed']),
        ];
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function filteredEventId(array $ids, mixed $eventId): int|false|null
    {
        if ($eventId === null || $eventId === '' || ! is_numeric($eventId)) {
            return null;
        }

        $eventId = (int) $eventId;

        if (! in_array($eventId, $ids, true)) {
            return false;
        }

        return $eventId;
    }

    private function applyDateRange(Builder $query, string $from, string $to): void
    {
        if ($from !== '') {
            $query->whereDate('registrations.registered_at', '>=', $from);
        }

        if ($to !== '') {
            $query->whereDate('registrations.registered_at', '<=', $to);
        }
    }

    private function percentage(int $numerator, int $denominator): ?int
    {
        if ($denominator <= 0) {
            return null;
        }

        return (int) round(($numerator / $denominator) * 100);
    }

    /**
     * @return array{
     *     rows: list<array{
     *         event: string,
     *         ticket_type: string|null,
     *         registrations: int,
     *         confirmed: int,
     *         checked_in: int,
     *         attendance_percentage: int|null,
     *     }>,
     *     total_registrations: int,
     *     total_confirmed: int,
     *     total_checked_in: int,
     *     attendance_percentage: int|null,
     * }
     */
    private function emptyResult(): array
    {
        return [
            'rows' => [],
            'total_registrations' => 0,
            'total_confirmed' => 0,
            'total_checked_in' => 0,
            'attendance_percentage' => null,
        ];
    }
}
