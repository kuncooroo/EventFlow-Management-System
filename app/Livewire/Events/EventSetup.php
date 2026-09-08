<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Event Setup')]
class EventSetup extends Component
{
    use AuthorizesRequests;

    public Event $event;

    public function mount(OrganizationContext $context): void
    {
        $organization = $context->resolveForUser(auth()->user());

        if ($organization === null || $this->event->organization_id !== $organization->id) {
            abort(404);
        }

        $this->authorize('view', $this->event);
    }

    public function render(): View
    {
        return view('livewire.events.event-setup', [
            'canView' => auth()->user()->can('view', $this->event),
            'canUpdate' => auth()->user()->can('update', $this->event),
            'canManageAssignments' => auth()->user()->can('manageAssignments', $this->event),
            'canPublish' => auth()->user()->can('publish', $this->event),
            'canCheckIn' => auth()->user()->can('checkIn', $this->event),
            'canManageLifecycle' => auth()->user()->can('markOngoing', $this->event)
                || auth()->user()->can('completeEvent', $this->event)
                || auth()->user()->can('cancelEvent', $this->event)
                || auth()->user()->can('archive', $this->event),
        ]);
    }
}
