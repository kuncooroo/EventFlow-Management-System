<?php

namespace App\Livewire\Events;

use App\Actions\Events\ArchiveEvent;
use App\Actions\Events\CancelEvent;
use App\Actions\Events\CompleteEvent;
use App\Actions\Events\MarkEventOngoing;
use App\Models\Event;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

class LifecycleActions extends Component
{
    use AuthorizesRequests;

    public Event $event;

    public function mount(Event $event): void
    {
        $this->event = $event;
    }

    public function startEvent(MarkEventOngoing $action): void
    {
        $this->perform(fn () => $action->handle($this->event, auth()->user()), 'Event started.');
    }

    public function completeEvent(CompleteEvent $action): void
    {
        $this->perform(fn () => $action->handle($this->event, auth()->user()), 'Event completed. It remains available for reporting.');
    }

    public function cancelEvent(CancelEvent $action): void
    {
        $this->perform(
            fn () => $action->handle($this->event, auth()->user()),
            'Event cancelled. New registrations are blocked and history is preserved.'
        );
    }

    public function archiveEvent(ArchiveEvent $action): void
    {
        $this->perform(
            fn () => $action->handle($this->event, auth()->user()),
            'Event archived. It is hidden from active lists but remains available in history.'
        );
    }

    public function render(): View
    {
        return view('livewire.events.lifecycle-actions', [
            'canStart' => Gate::allows('markOngoing', $this->event),
            'canComplete' => Gate::allows('completeEvent', $this->event),
            'canCancel' => Gate::allows('cancelEvent', $this->event),
            'canArchive' => Gate::allows('archive', $this->event),
        ]);
    }

    private function perform(callable $transition, string $successMessage): void
    {
        try {
            $transition();
            $this->event = $this->event->refresh();
            session()->flash('status', $successMessage);
        } catch (ValidationException $e) {
            session()->flash('lifecycle_error', $e->errors()['lifecycle'][0] ?? 'That action is not allowed for the event\'s current state.');
        }
    }
}
