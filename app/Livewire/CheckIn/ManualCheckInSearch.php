<?php

namespace App\Livewire\CheckIn;

use App\Actions\CheckIns\CheckInAttendee;
use App\Data\CheckIns\CheckInResult;
use App\Enums\CheckInMethod;
use App\Enums\CheckInOutcome;
use App\Models\Event;
use App\Models\Registration;
use App\Queries\Attendees\EventAttendeeSearchQuery;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Check-In')]
class ManualCheckInSearch extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public Event $event;

    public string $search = '';

    public ?string $resultOutcome = null;

    public ?string $resultTitle = null;

    public ?string $resultDetail = null;

    public function mount(OrganizationContext $context): void
    {
        $organization = $context->resolveForUser(auth()->user());

        if ($organization === null || $this->event->organization_id !== $organization->id) {
            abort(404);
        }

        $this->authorize('checkIn', $this->event);
    }

    public function updatingSearch(): void
    {
        $this->clearResult();
        $this->resetPage();
    }

    public function checkIn(int $registrationId, CheckInAttendee $action): void
    {
        $registration = Registration::query()
            ->where('event_id', $this->event->id)
            ->findOrFail($registrationId);

        $result = $action->handle(
            $this->event,
            $registration,
            auth()->user(),
            CheckInMethod::Manual,
        );

        $this->storeResult($result, $registration);
    }

    public function clearResult(): void
    {
        $this->resultOutcome = null;
        $this->resultTitle = null;
        $this->resultDetail = null;
    }

    public function render(EventAttendeeSearchQuery $searchQuery): View
    {
        $attendees = $searchQuery($this->event, $this->search !== '' ? ['search' => $this->search] : []);

        return view('livewire.check-in.manual-check-in-search', [
            'attendees' => $attendees,
            'hasSearched' => $this->search !== '',
        ]);
    }

    private function storeResult(CheckInResult $result, Registration $registration): void
    {
        $this->clearResult();

        $this->resultOutcome = $result->outcome->value;

        if ($result->outcome === CheckInOutcome::Success) {
            $this->resultTitle = $registration->attendee_name;
            $this->resultDetail = 'Checked in at '.$result->checkIn->checked_in_at?->format('g:i A');
        } elseif ($result->outcome === CheckInOutcome::Duplicate) {
            $this->resultTitle = $registration->attendee_name;
            $this->resultDetail = 'Previously checked in at '.$result->previousCheckIn->checked_in_at?->format('g:i A');
        } else {
            $this->resultDetail = $result->message ?? 'This entry cannot be used for this event.';
        }
    }
}
