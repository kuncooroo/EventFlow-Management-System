<?php

namespace App\Actions\Reports;

use App\Enums\ExportType;
use App\Enums\RegistrationStatus;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use App\Queries\Reports\AccessibleEventsQuery;
use App\Queries\Reports\AttendanceReportQuery;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams CSV exports for accessible events without loading the whole
 * dataset into memory (SRS 29.2). Only fields the user is authorized to view
 * are emitted; ticket QR secrets are never exported (FR-EXP-002, PRD security).
 */
class ExportCsv
{
    private const ATTENDEE_COLUMNS = [
        'Registration Code',
        'Event',
        'Ticket Type',
        'Ticket Code',
        'Status',
        'Checked In',
        'Attendee Name',
        'Attendee Email',
        'Attendee Phone',
        'Attendee Organization',
        'Registered At',
        'Cancelled At',
    ];

    private const ATTENDANCE_COLUMNS = [
        'Event',
        'Ticket Type',
        'Registrations',
        'Confirmed',
        'Checked In',
        'Attendance',
    ];

    public function __construct(
        private readonly AccessibleEventsQuery $events,
        private readonly AttendanceReportQuery $attendanceReport,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function stream(Organization $organization, User $user, ExportType $type, array $filters): StreamedResponse
    {
        $timezone = $organization->timezone ?: config('app.timezone');

        return response()->streamDownload(
            function () use ($organization, $user, $type, $filters, $timezone): void {
                $stream = fopen('php://output', 'w');

                if ($stream === false) {
                    throw new \RuntimeException('Unable to open output stream.');
                }

                fwrite($stream, "\xEF\xBB\xBF");
                fputcsv($stream, $this->columns($type));

                foreach ($this->rows($organization, $user, $type, $filters, $timezone) as $row) {
                    fputcsv($stream, $row);
                }

                fclose($stream);
            },
            $this->fileName($organization, $type),
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    /**
     * @return list<string>
     */
    private function columns(ExportType $type): array
    {
        return match ($type) {
            ExportType::Attendees, ExportType::Registrations => self::ATTENDEE_COLUMNS,
            ExportType::Attendance => self::ATTENDANCE_COLUMNS,
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return iterable<list<string>>
     */
    private function rows(Organization $organization, User $user, ExportType $type, array $filters, string $timezone): iterable
    {
        return match ($type) {
            ExportType::Attendees, ExportType::Registrations => $this->registrationRows($organization, $user, $type, $filters, $timezone),
            ExportType::Attendance => $this->attendanceRows($organization, $user, $filters),
        };
    }

    private function fileName(Organization $organization, ExportType $type): string
    {
        return sprintf(
            '%s-%s-%s.csv',
            $organization->slug,
            $type->value,
            now()->format('Y-m-d-His'),
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return \Generator<list<string>>
     */
    private function registrationRows(
        Organization $organization,
        User $user,
        ExportType $type,
        array $filters,
        string $timezone,
    ): \Generator {
        $ids = ($this->events)($organization, $user)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $query = Registration::query()
            ->whereIn('registrations.event_id', $ids)
            ->with(['event', 'ticket', 'ticketType'])
            ->select('registrations.*')
            ->selectRaw('exists (select 1 from check_ins where check_ins.registration_id = registrations.id) as is_checked_in');

        if ($type === ExportType::Attendees) {
            $query->where('registrations.event_id', (int) ($filters['event_id'] ?? 0));
            $this->applySearch($query, (string) ($filters['search'] ?? ''));
            $this->applyCheckInFilter($query, (string) ($filters['checked_in'] ?? ''));
        } elseif (isset($filters['event_id']) && (string) $filters['event_id'] !== '') {
            $query->where('registrations.event_id', (int) $filters['event_id']);
        }

        $this->applyStatusFilter($query, (string) ($filters['status'] ?? ''));
        $this->applyTicketTypeFilter($query, $filters['ticket_type_id'] ?? null);
        $this->applyDateRange($query, (string) ($filters['registered_from'] ?? ''), (string) ($filters['registered_to'] ?? ''));

        $lastId = 0;

        while (true) {
            $page = (clone $query)
                ->where('registrations.id', '>', $lastId)
                ->orderBy('registrations.id')
                ->limit(500)
                ->get();

            if ($page->isEmpty()) {
                return;
            }

            foreach ($page as $registration) {
                $lastId = $registration->id;

                yield $this->registrationRow($registration, $timezone);
            }
        }
    }

    /**
     * @return iterable
     */
    private function registrationRow(Registration $registration, string $timezone): array
    {
        return [
            $registration->registration_code,
            $registration->event?->name ?? '',
            $registration->ticketType?->name ?? '',
            $registration->ticket?->ticket_code ?? '',
            $registration->status->label(),
            (int) $registration->is_checked_in === 1 ? 'Yes' : 'No',
            $registration->attendee_name,
            $registration->attendee_email,
            $registration->attendee_phone ?? '',
            $registration->attendee_organization ?? '',
            $this->formatDateTime($registration->registered_at, $timezone),
            $this->formatDateTime($registration->cancelled_at, $timezone),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return \Generator<list<string>>
     */
    private function attendanceRows(Organization $organization, User $user, array $filters): \Generator
    {
        $data = ($this->attendanceReport)($organization, $user, $filters);

        foreach ($data['rows'] as $row) {
            yield [
                $row['event'],
                $row['ticket_type'] ?? '',
                (string) $row['registrations'],
                (string) $row['confirmed'],
                (string) $row['checked_in'],
                $row['attendance_percentage'] === null ? 'Not available' : $row['attendance_percentage'].'%',
            ];
        }
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
            $query->where('registrations.ticket_type_id', (int) $ticketTypeId);
        }
    }

    private function applyCheckInFilter(Builder $query, string $checkedIn): void
    {
        if (! in_array($checkedIn, ['0', '1'], true)) {
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
            $query->whereDate('registrations.registered_at', '>=', $from);
        }

        if ($to !== '') {
            $query->whereDate('registrations.registered_at', '<=', $to);
        }
    }

    private function formatDateTime(?CarbonInterface $date, string $timezone): string
    {
        if ($date === null) {
            return '';
        }

        return $date->setTimezone($timezone)->format('Y-m-d H:i:s');
    }
}
