<?php

namespace App\Livewire\Reports;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Organization;
use App\Policies\ReportPolicy;
use App\Queries\Reports\AccessibleEventsQuery;
use App\Queries\Reports\RegistrationReportQuery;
use App\Support\Organization\OrganizationContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Registration Report')]
class RegistrationReport extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public Organization $organization;

    public string $eventFilter = '';

    public string $statusFilter = '';

    public string $ticketTypeFilter = '';

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

    public function updatedEventFilter(): void
    {
        $this->ticketTypeFilter = '';
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTicketTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('eventFilter', 'statusFilter', 'ticketTypeFilter', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    public function render(RegistrationReportQuery $query, AccessibleEventsQuery $events): View
    {
        $filters = $this->sanitizedFilters();

        $data = $query($this->organization, auth()->user(), $filters);
        $accessibleEvents = $events($this->organization, auth()->user())
            ->orderBy('start_at')
            ->get();

        $selectedEvent = $this->selectedEvent($accessibleEvents);

        return view('livewire.reports.registration-report', [
            'events' => $accessibleEvents,
            'ticketTypes' => $selectedEvent?->ticketTypes()->ordered()->get() ?? collect(),
            'registrations' => $data['registrations'],
            'total' => $data['total'],
            'confirmed' => $data['confirmed'],
            'cancelled' => $data['cancelled'],
            'byTicketType' => $data['by_ticket_type'],
            'filtersActive' => $filters !== [],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function sanitizedFilters(): array
    {
        $validated = Validator::make($this->only([
            'eventFilter', 'statusFilter', 'ticketTypeFilter', 'dateFrom', 'dateTo',
        ]), [
            'eventFilter' => ['nullable', 'numeric'],
            'statusFilter' => ['nullable', Rule::enum(RegistrationStatus::class)],
            'ticketTypeFilter' => ['nullable', 'numeric'],
            'dateFrom' => ['nullable', 'date'],
            'dateTo' => ['nullable', 'date'],
        ])->valid();

        $filters = [];

        if (($validated['eventFilter'] ?? '') !== '') {
            $filters['event_id'] = $validated['eventFilter'];
        }

        if (($validated['statusFilter'] ?? '') !== '') {
            $filters['status'] = $validated['statusFilter'];
        }

        if (($validated['ticketTypeFilter'] ?? '') !== '') {
            $filters['ticket_type_id'] = $validated['ticketTypeFilter'];
        }

        if (($validated['dateFrom'] ?? '') !== '') {
            $filters['registered_from'] = $validated['dateFrom'];
        }

        if (($validated['dateTo'] ?? '') !== '') {
            $filters['registered_to'] = $validated['dateTo'];
        }

        return $filters;
    }

    private function selectedEvent(Collection $events): ?Event
    {
        if ($this->eventFilter === '' || ! is_numeric($this->eventFilter)) {
            return null;
        }

        return $events->firstWhere('id', (int) $this->eventFilter);
    }
}
