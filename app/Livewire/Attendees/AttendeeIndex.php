<?php

namespace App\Livewire\Attendees;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Registration;
use App\Queries\Attendees\EventAttendeeSearchQuery;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Attendees')]
class AttendeeIndex extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public Event $event;

    public string $search = '';

    public string $statusFilter = '';

    public string $ticketTypeFilter = '';

    public string $checkedInFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(OrganizationContext $context): void
    {
        $organization = $context->resolveForUser(auth()->user());

        if ($organization === null || $this->event->organization_id !== $organization->id) {
            abort(404);
        }

        $this->authorize('viewAny', [Registration::class, $this->event]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTicketTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCheckedInFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'statusFilter', 'ticketTypeFilter', 'checkedInFilter', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    public function render(EventAttendeeSearchQuery $searchQuery): View
    {
        $filters = $this->sanitizedFilters();

        $attendees = $searchQuery($this->event, $filters);

        return view('livewire.attendees.attendee-index', [
            'attendees' => $attendees,
            'ticketTypes' => $this->event->ticketTypes()->ordered()->get(),
            'statuses' => RegistrationStatus::cases(),
            'filtersActive' => $filters !== [],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function sanitizedFilters(): array
    {
        $validated = Validator::make($this->only([
            'search', 'statusFilter', 'ticketTypeFilter', 'checkedInFilter', 'dateFrom', 'dateTo',
        ]), [
            'search' => ['nullable', 'string', 'max:100'],
            'statusFilter' => ['nullable', Rule::enum(RegistrationStatus::class)],
            'ticketTypeFilter' => ['nullable', 'numeric'],
            'checkedInFilter' => ['nullable', Rule::in(['0', '1'])],
            'dateFrom' => ['nullable', 'date'],
            'dateTo' => ['nullable', 'date'],
        ])->valid();

        $filters = [];

        if (($validated['search'] ?? '') !== '') {
            $filters['search'] = $validated['search'];
        }

        if (($validated['statusFilter'] ?? '') !== '') {
            $filters['status'] = $validated['statusFilter'];
        }

        if (($validated['ticketTypeFilter'] ?? '') !== '') {
            $filters['ticket_type_id'] = $validated['ticketTypeFilter'];
        }

        if (($validated['checkedInFilter'] ?? '') !== '') {
            $filters['checked_in'] = $validated['checkedInFilter'];
        }

        if (($validated['dateFrom'] ?? '') !== '') {
            $filters['registered_from'] = $validated['dateFrom'];
        }

        if (($validated['dateTo'] ?? '') !== '') {
            $filters['registered_to'] = $validated['dateTo'];
        }

        return $filters;
    }
}
