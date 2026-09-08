<?php

namespace App\Livewire\Attendees;

use App\Actions\CheckIns\CheckInAttendee;
use App\Enums\CheckInMethod;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Livewire\Component;

class CheckInAction extends Component
{
    use AuthorizesRequests;

    public Event $event;

    public Registration $registration;

    public function mount(Event $event, Registration $registration): void
    {
        abort_if($registration->event_id !== $event->id, 404);

        $this->event = $event;
        $this->registration = $registration;

        $this->registration->is_checked_in = $this->isCheckedIn();
    }

    public function checkIn(): void
    {
        Gate::authorize('checkIn', [$this->registration, $this->event]);

        $result = app(CheckInAttendee::class)->handle(
            $this->event,
            $this->registration,
            auth()->user(),
            CheckInMethod::Manual,
        );

        if ($result->isSuccess()) {
            $this->redirectRoute(
                'app.events.attendees.show',
                [$this->event, $this->registration],
                navigate: true,
            );
            session()->flash('status', 'Attendee checked in successfully.');
        } else {
            session()->flash(
                'checkin_error',
                $result->message
                    ?? ($result->outcome->value === 'duplicate'
                        ? 'This attendee is already checked in.'
                        : 'This attendee cannot be checked in.'),
            );
        }
    }

    public function render(): View
    {
        return view('livewire.attendees.check-in-action', [
            'registration' => $this->registration,
            'canCheckIn' => auth()->user()->can('checkIn', [$this->registration, $this->event]),
        ]);
    }

    private function isCheckedIn(): bool
    {
        if (! Schema::hasTable('check_ins')) {
            return false;
        }

        return DB::table('check_ins')
            ->where('registration_id', $this->registration->id)
            ->exists();
    }
}
