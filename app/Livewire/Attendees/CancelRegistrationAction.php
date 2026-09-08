<?php

namespace App\Livewire\Attendees;

use App\Actions\Registrations\CancelRegistration;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

class CancelRegistrationAction extends Component
{
    use AuthorizesRequests;

    public Event $event;

    public Registration $registration;

    public function mount(Event $event, Registration $registration): void
    {
        abort_if($registration->event_id !== $event->id, 404);

        $this->event = $event;
        $this->registration = $registration;
    }

    public function cancel(): void
    {
        Gate::authorize('cancel', [$this->registration, $this->event]);

        try {
            app(CancelRegistration::class)->handle($this->event, $this->registration, auth()->user());
            $this->redirectRoute(
                'app.events.attendees.show',
                [$this->event, $this->registration],
                navigate: true,
            );
            session()->flash('status', 'Registration cancelled. The attendee can no longer check in.');
        } catch (ValidationException $e) {
            session()->flash('cancel_error', $e->errors()['registration'][0] ?? 'The registration could not be cancelled.');
        }
    }

    public function render(): View
    {
        return view('livewire.attendees.cancel-registration-action', [
            'registration' => $this->registration,
            'canCancel' => auth()->user()->can('cancel', [$this->registration, $this->event]),
        ]);
    }
}
