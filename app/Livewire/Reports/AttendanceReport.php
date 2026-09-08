<?php

namespace App\Livewire\Reports;

use App\Models\Organization;
use App\Policies\ReportPolicy;
use App\Queries\Reports\AccessibleEventsQuery;
use App\Queries\Reports\AttendanceReportQuery;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Attendance Report')]
class AttendanceReport extends Component
{
    use AuthorizesRequests;

    public Organization $organization;

    public string $eventFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(OrganizationContext $context): void
    {
        $organization = $context->resolveForUser(auth()->user());

        if ($organization === null) {
            abort(403);
        }

        $this->organization = $organization;

        $this->authorize('viewAny', [ReportPolicy::class]);

        $this->eventFilter = (string) request()->query('event_id', '');
    }

    public function clearFilters(): void
    {
        $this->reset('eventFilter', 'dateFrom', 'dateTo');
    }

    public function render(AttendanceReportQuery $query, AccessibleEventsQuery $events): View
    {
        $data = $query($this->organization, auth()->user(), $this->sanitizedFilters());

        return view('livewire.reports.attendance-report', [
            'rows' => $data['rows'],
            'totalRegistrations' => $data['total_registrations'],
            'totalConfirmed' => $data['total_confirmed'],
            'totalCheckedIn' => $data['total_checked_in'],
            'attendancePercentage' => $data['attendance_percentage'],
            'events' => $events($this->organization, auth()->user())->orderBy('start_at')->get(),
            'filtersActive' => $this->filtersActive(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function sanitizedFilters(): array
    {
        $validated = Validator::make($this->only(['eventFilter', 'dateFrom', 'dateTo']), [
            'eventFilter' => ['nullable', 'numeric'],
            'dateFrom' => ['nullable', 'date'],
            'dateTo' => ['nullable', 'date'],
        ])->valid();

        $filters = [];

        if (($validated['eventFilter'] ?? '') !== '') {
            $filters['event_id'] = $validated['eventFilter'];
        }

        if (($validated['dateFrom'] ?? '') !== '') {
            $filters['registered_from'] = $validated['dateFrom'];
        }

        if (($validated['dateTo'] ?? '') !== '') {
            $filters['registered_to'] = $validated['dateTo'];
        }

        return $filters;
    }

    private function filtersActive(): bool
    {
        return $this->sanitizedFilters() !== [];
    }
}
