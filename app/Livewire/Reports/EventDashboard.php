<?php

namespace App\Livewire\Reports;

use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Registration;
use App\Models\TicketType;
use App\Queries\Dashboard\EventDashboardQuery;
use App\Support\Organization\OrganizationContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Event Dashboard')]
class EventDashboard extends Component
{
    use AuthorizesRequests;

    public Event $event;

    public int $totalRegistrations = 0;

    public int $confirmedRegistrations = 0;

    public int $checkedIn = 0;

    public ?int $attendancePercentage = null;

    /** @var Collection<int, TicketType> */
    public Collection $ticketTypes;

    /** @var Collection<int, Registration> */
    public Collection $recentRegistrations;

    /** @var Collection<int, CheckIn> */
    public Collection $recentCheckIns;

    public function mount(OrganizationContext $context): void
    {
        $organization = $context->resolveForUser(auth()->user());

        if ($organization === null || $this->event->organization_id !== $organization->id) {
            abort(404);
        }

        $this->authorize('view', $this->event);
    }

    public function render(EventDashboardQuery $query): View
    {
        $data = $query($this->event);

        $this->totalRegistrations = $data['total_registrations'];
        $this->confirmedRegistrations = $data['confirmed_registrations'];
        $this->checkedIn = $data['checked_in'];
        $this->attendancePercentage = $data['attendance_percentage'];
        $this->ticketTypes = $data['ticket_types'];
        $this->recentRegistrations = $data['recent_registrations'];
        $this->recentCheckIns = $data['recent_check_ins'];

        return view('livewire.reports.event-dashboard');
    }
}
