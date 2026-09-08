<?php

namespace App\Livewire\Attendees;

use App\Models\Event;
use App\Models\Registration;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Attendee Details')]
class AttendeeShow extends Component
{
    use AuthorizesRequests;

    public Event $event;

    public Registration $registration;

    public function mount(OrganizationContext $context): void
    {
        abort_if($this->registration->event_id !== $this->event->id, 404);

        $organization = $context->resolveForUser(auth()->user());

        if ($organization === null || $this->event->organization_id !== $organization->id) {
            abort(404);
        }

        $this->authorize('view', [$this->registration, $this->event]);
    }

    public function render(): View
    {
        $isCheckedIn = false;

        if (Schema::hasTable('check_ins')) {
            $isCheckedIn = DB::table('check_ins')
                ->where('registration_id', $this->registration->id)
                ->exists();
        }

        $this->registration->load(['ticketType', 'ticket', 'answers', 'cancelledBy']);

        return view('livewire.attendees.attendee-show', [
            'registration' => $this->registration,
            'isCheckedIn' => $isCheckedIn,
        ]);
    }
}
