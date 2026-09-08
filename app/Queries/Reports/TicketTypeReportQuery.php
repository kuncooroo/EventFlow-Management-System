<?php

namespace App\Queries\Reports;

use App\Models\Organization;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\User;
use App\Queries\Reports\Concerns\ScopesToAccessibleEvents;
use Illuminate\Database\Eloquent\Builder;

class TicketTypeReportQuery
{
    use ScopesToAccessibleEvents;

    /**
     * Ticket-type totals for accessible events, aggregated in SQL (FR-RPT-003).
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     ticket_types: list<array{
     *         id: int,
     *         name: string,
     *         event: string,
     *         is_active: bool,
     *         capacity: int|null,
     *         price: string,
     *         registrations: int,
     *         confirmed: int,
     *         cancelled: int,
     *         checked_in: int,
     *     }>,
     *     totals: array{registrations: int, confirmed: int, cancelled: int, checked_in: int},
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

        $registrations = Registration::query()
            ->whereIn('registrations.event_id', $ids)
            ->whereNotNull('registrations.ticket_type_id');

        if ($eventId !== null) {
            $registrations->where('registrations.event_id', $eventId);
        }

        $this->applyDateRange(
            $registrations,
            (string) ($filters['registered_from'] ?? ''),
            (string) ($filters['registered_to'] ?? ''),
        );

        $counts = $this->countsByType($registrations);
        $checkedIn = $this->checkedInByType($registrations);

        $types = TicketType::query()
            ->whereIn('event_id', $ids)
            ->with('event')
            ->ordered();

        if ($eventId !== null) {
            $types->where('event_id', $eventId);
        }

        $rows = [];

        foreach ($types->get() as $type) {
            $totals = $counts[$type->id] ?? ['total' => 0, 'confirmed' => 0, 'cancelled' => 0];
            $checkedInTotal = (int) ($checkedIn[$type->id] ?? 0);

            $rows[] = [
                'id' => $type->id,
                'name' => $type->name,
                'event' => $type->event?->name ?? '—',
                'is_active' => (bool) $type->is_active,
                'capacity' => $type->capacity,
                'price' => (string) $type->price_amount,
                'registrations' => $totals['total'],
                'confirmed' => $totals['confirmed'],
                'cancelled' => $totals['cancelled'],
                'checked_in' => $checkedInTotal,
            ];
        }

        usort($rows, fn (array $a, array $b): int => $b['registrations'] <=> $a['registrations']);

        $totals = ['registrations' => 0, 'confirmed' => 0, 'cancelled' => 0, 'checked_in' => 0];

        foreach ($rows as $row) {
            $totals['registrations'] += $row['registrations'];
            $totals['confirmed'] += $row['confirmed'];
            $totals['cancelled'] += $row['cancelled'];
            $totals['checked_in'] += $row['checked_in'];
        }

        return [
            'ticket_types' => $rows,
            'totals' => $totals,
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

    /**
     * @return array<int, array{total: int, confirmed: int, cancelled: int}>
     */
    private function countsByType(Builder $registrations): array
    {
        $rows = (clone $registrations)->toBase()
            ->selectRaw('registrations.ticket_type_id, registrations.status, count(*) as total')
            ->groupBy('registrations.ticket_type_id', 'registrations.status')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $id = (int) $row->ticket_type_id;

            if (! isset($counts[$id])) {
                $counts[$id] = ['total' => 0, 'confirmed' => 0, 'cancelled' => 0];
            }

            $total = (int) $row->total;
            $counts[$id]['total'] += $total;

            if ($row->status === 'confirmed') {
                $counts[$id]['confirmed'] += $total;
            } else {
                $counts[$id]['cancelled'] += $total;
            }
        }

        return $counts;
    }

    /**
     * @return array<int, int>
     */
    private function checkedInByType(Builder $registrations): array
    {
        return (clone $registrations)->toBase()
            ->join('check_ins', 'check_ins.registration_id', '=', 'registrations.id')
            ->selectRaw('registrations.ticket_type_id as type_id, count(*) as total')
            ->groupBy('registrations.ticket_type_id')
            ->pluck('total', 'type_id')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();
    }

    /**
     * @return array{
     *     ticket_types: list<array{
     *         id: int,
     *         name: string,
     *         event: string,
     *         is_active: bool,
     *         capacity: int|null,
     *         price: string,
     *         registrations: int,
     *         confirmed: int,
     *         cancelled: int,
     *         checked_in: int,
     *     }>,
     *     totals: array{registrations: int, confirmed: int, cancelled: int, checked_in: int},
     * }
     */
    private function emptyResult(): array
    {
        return [
            'ticket_types' => [],
            'totals' => ['registrations' => 0, 'confirmed' => 0, 'cancelled' => 0, 'checked_in' => 0],
        ];
    }
}
