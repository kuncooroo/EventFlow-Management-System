<?php

namespace App\Queries\Reports;

use App\Enums\RegistrationStatus;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use App\Queries\Reports\Concerns\ScopesToAccessibleEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class RegistrationReportQuery
{
    use ScopesToAccessibleEvents;

    /**
     * Registration summary + filtered detail rows for accessible events.
     * Totals reconcile with the underlying filtered records (FR-RPT-001,
     * SFR-RPT-001).
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     total: int,
     *     confirmed: int,
     *     cancelled: int,
     *     by_status: array<string, int>,
     *     by_ticket_type: list<array{ticket_type: string, total: int, confirmed: int, cancelled: int}>,
     *     registrations: LengthAwarePaginator,
     * }
     */
    public function __invoke(Organization $organization, User $user, array $filters = [], int $perPage = 15): array
    {
        $ids = $this->accessibleEventIds($organization, $user);

        if ($ids === []) {
            return $this->emptyResult();
        }

        $base = Registration::query()
            ->whereIn('registrations.event_id', $ids)
            ->with(['ticketType', 'event']);

        $eventId = $this->filteredEventId($ids, $filters['event_id'] ?? null);
        if ($eventId === false) {
            return $this->emptyResult();
        }

        if ($eventId !== null) {
            $base->where('registrations.event_id', $eventId);
        }

        $this->applyStatusFilter($base, (string) ($filters['status'] ?? ''));
        $this->applyTicketTypeFilter($base, $filters['ticket_type_id'] ?? null);
        $this->applyDateRange(
            $base,
            (string) ($filters['registered_from'] ?? ''),
            (string) ($filters['registered_to'] ?? ''),
        );

        $statusCounts = (clone $base)->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn (mixed $value): int => (int) $value);

        $confirmed = (int) ($statusCounts[RegistrationStatus::Confirmed->value] ?? 0);
        $cancelled = (int) ($statusCounts[RegistrationStatus::Cancelled->value] ?? 0);

        return [
            'total' => $confirmed + $cancelled,
            'confirmed' => $confirmed,
            'cancelled' => $cancelled,
            'by_status' => [
                RegistrationStatus::Confirmed->value => $confirmed,
                RegistrationStatus::Cancelled->value => $cancelled,
            ],
            'by_ticket_type' => $this->ticketTypeBreakdown($base),
            'registrations' => $base
                ->orderByDesc('registered_at')
                ->orderByDesc('id')
                ->paginate($perPage),
        ];
    }

    /**
     * Returns the accessible event id for the filter, null when no event filter
     * is set, or false when an inaccessible event id was posted.
     *
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

    private function applyStatusFilter(Builder $query, string $status): void
    {
        if ($status !== '' && RegistrationStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        }
    }

    private function applyTicketTypeFilter(Builder $query, mixed $ticketTypeId): void
    {
        if ($ticketTypeId !== null && $ticketTypeId !== '' && is_numeric($ticketTypeId)) {
            $query->where('registrations.ticket_type_id', (int) $ticketTypeId);
        }
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
     * Per-ticket-type registration breakdown, aggregated in SQL.
     *
     * @return list<array{ticket_type: string, total: int, confirmed: int, cancelled: int}>
     */
    private function ticketTypeBreakdown(Builder $base): array
    {
        $rows = (clone $base)->toBase()
            ->leftJoin('ticket_types', 'ticket_types.id', '=', 'registrations.ticket_type_id')
            ->selectRaw(
                'COALESCE(ticket_types.name, ?) as ticket_type_name, registrations.status, count(*) as total',
                ['No ticket type'],
            )
            ->groupBy('ticket_type_name', 'registrations.status')
            ->get();

        $byType = [];

        foreach ($rows as $row) {
            $name = $row->ticket_type_name;

            if (! isset($byType[$name])) {
                $byType[$name] = ['ticket_type' => $name, 'total' => 0, 'confirmed' => 0, 'cancelled' => 0];
            }

            $count = (int) $row->total;
            $byType[$name]['total'] += $count;

            if ($row->status === RegistrationStatus::Confirmed->value) {
                $byType[$name]['confirmed'] += $count;
            } else {
                $byType[$name]['cancelled'] += $count;
            }
        }

        $byType = array_values($byType);

        usort($byType, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return $byType;
    }

    /**
     * @return array{
     *     total: int,
     *     confirmed: int,
     *     cancelled: int,
     *     by_status: array<string, int>,
     *     by_ticket_type: list<array{ticket_type: string, total: int, confirmed: int, cancelled: int}>,
     *     registrations: LengthAwarePaginator,
     * }
     */
    private function emptyResult(): array
    {
        return [
            'total' => 0,
            'confirmed' => 0,
            'cancelled' => 0,
            'by_status' => [
                RegistrationStatus::Confirmed->value => 0,
                RegistrationStatus::Cancelled->value => 0,
            ],
            'by_ticket_type' => [],
            'registrations' => Registration::query()->whereRaw('1 = 0')->paginate(15),
        ];
    }
}
