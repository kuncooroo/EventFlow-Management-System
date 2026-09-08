<?php

namespace App\Livewire\Events;

use App\Actions\Events\PublishEvent;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Services\Events\PublishReadinessChecker;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PublishEventButton extends Component
{
    use AuthorizesRequests;

    public Event $event;

    public function mount(Event $event): void
    {
        $this->event = $event;

        Gate::authorize('publish', $this->event);
    }

    public function publish(PublishEvent $action): void
    {
        Gate::authorize('publish', $this->event);

        try {
            $action->handle($this->event, auth()->user());
            $this->event = $this->event->refresh();
            session()->flash('status', 'Event published. It is now publicly accessible.');
        } catch (ValidationException $e) {
            session()->flash('publish_error', $e->errors()['publish'][0] ?? 'The event could not be published.');
        }
    }

    public function render(PublishReadinessChecker $checker)
    {
        return view('livewire.events.publish-event-button', [
            'readinessFailures' => $this->event->status === EventStatus::Draft ? $checker->failures($this->event) : [],
            'canPublish' => auth()->user()->can('publish', $this->event),
        ]);
    }
}
