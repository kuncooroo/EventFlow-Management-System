<?php

namespace App\Queries\Attendees;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class EventAttendeeSearchQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __invoke(Event $event, array $filters = []): LengthAwarePaginator
    {
        $hasCheckIns = Schema::hasTable('check_ins');

        $query = Registration::query()
            ->where('event_id', $event->id)
            ->with(['ticketType', 'ticket']);

        if ($hasCheckIns) {
            $query->select('registrations.*')
                ->selectRaw('exists (select 1 from check_ins where check_ins.registration_id = registrations.id) as is_checked_in');
        }

        $this->applySearch($query, (string) ($filters['search'] ?? ''));
        $this->applyStatusFilter($query, (string) ($filters['status'] ?? ''));
        $this->applyTicketTypeFilter($query, $filters['ticket_type_id'] ?? null);
        $this->applyCheckInFilter($query, (string) ($filters['checked_in'] ?? ''), $hasCheckIns);
        $this->applyDateRange(
            $query,
            (string) ($filters['registered_from'] ?? ''),
            (string) ($filters['registered_to'] ?? ''),
        );

        return $query->orderByDesc('registered_at')->paginate(15);
    }

    private function applySearch(Builder $query, string $search): void
    {
        $search = trim($search);

        if ($search === '') {
            return;
        }

        $term = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $query->where(function (Builder $nested) use ($term): void {
            $nested->where('attendee_name', 'like', "%{$term}%")
                ->orWhere('attendee_email', 'like', "%{$term}%")
                ->orWhere('registration_code', 'like', "%{$term}%")
                ->orWhereHas('ticket', fn (Builder $ticket) => $ticket->where('ticket_code', 'like', "%{$term}%"));
        });
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
            $query->where('ticket_type_id', (int) $ticketTypeId);
        }
    }

    private function applyCheckInFilter(Builder $query, string $checkedIn, bool $hasCheckIns): void
    {
        if (! in_array($checkedIn, ['0', '1'], true)) {
            return;
        }

        if (! $hasCheckIns) {
            if ($checkedIn === '1') {
                $query->whereRaw('1 = 0');
            }

            return;
        }

        $query->whereRaw(
            'exists (select 1 from check_ins where check_ins.registration_id = registrations.id) = ?',
            [$checkedIn === '1' ? 1 : 0],
        );
    }

    private function applyDateRange(Builder $query, string $from, string $to): void
    {
        if ($from !== '') {
            $query->whereDate('registered_at', '>=', $from);
        }

        if ($to !== '') {
            $query->whereDate('registered_at', '<=', $to);
        }
    }
}
