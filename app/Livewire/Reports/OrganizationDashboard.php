<?php

namespace App\Livewire\Reports;

use App\Models\Event;
use App\Models\Organization;
use App\Queries\Dashboard\OrganizationDashboardQuery;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class OrganizationDashboard extends Component
{
    use AuthorizesRequests;

    public Organization $organization;

    /** @var array<string, int> */
    public array $statusCounts = [];

    public int $totalEvents = 0;

    public int $activeEvents = 0;

    public int $upcomingEvents = 0;

    public int $ongoingEvents = 0;

    public int $completedEvents = 0;

    public int $totalRegistrations = 0;

    public int $confirmedRegistrations = 0;

    public int $checkedIn = 0;

    /** @var Collection<int, Event> */
    public Collection $upcoming;

    /** @var Collection<int, Event> */
    public Collection $recent;

    public function mount(OrganizationContext $context): void
    {
        $organization = $context->resolveForUser(auth()->user());

        if ($organization === null) {
            abort(403);
        }

        $this->organization = $organization;

        $this->authorize('viewAny', Event::class);
    }

    public function render(OrganizationDashboardQuery $query): View
    {
        $data = $query($this->organization, auth()->user());

        $this->statusCounts = $data['status_counts'];
        $this->totalEvents = $data['total_events'];
        $this->activeEvents = $data['active_events'];
        $this->upcomingEvents = $data['upcoming_events'];
        $this->ongoingEvents = $data['ongoing_events'];
        $this->completedEvents = $data['completed_events'];
        $this->totalRegistrations = $data['total_registrations'];
        $this->confirmedRegistrations = $data['confirmed_registrations'];
        $this->checkedIn = $data['checked_in'];
        $this->upcoming = $data['upcoming'];
        $this->recent = $data['recent'];

        return view('livewire.reports.organization-dashboard');
    }
}
