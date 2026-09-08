<?php

namespace App\Livewire\Events;

use App\Actions\Events\UpsertVenue;
use App\Enums\VenueMode;
use App\Models\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class VenueForm extends Component
{
    public Event $event;

    public string $mode = '';

    public string $name = '';

    public string $address = '';

    public string $notes = '';

    public bool $is_public = true;

    public function mount(Event $event): void
    {
        $this->event = $event;

        Gate::authorize('update', $this->event);

        $this->mode = $this->event->mode ?? VenueMode::Online->value;

        if ($this->event->venue) {
            $this->name = $this->event->venue->name;
            $this->address = $this->event->venue->address ?? '';
            $this->notes = $this->event->venue->notes ?? '';
            $this->is_public = $this->event->venue->is_public;
        }
    }

    public function save(UpsertVenue $action): void
    {
        Gate::authorize('update', $this->event);

        $this->validate([
            'mode' => ['required', Rule::in(array_column(VenueMode::cases(), 'value'))],
            'name' => 'nullable|string|max:200',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        if ($this->mode !== VenueMode::Online->value && trim($this->name) === '') {
            $this->addError('name', 'The venue name is required for offline or hybrid events.');

            return;
        }

        try {
            $action->handle($this->event, auth()->user(), [
                'mode' => $this->mode,
                'name' => $this->name,
                'address' => $this->address,
                'notes' => $this->notes,
                'is_public' => $this->is_public,
            ]);

            session()->flash('status', 'Venue settings saved.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        }
    }

    public function render()
    {
        return view('livewire.events.venue-form', [
            'modes' => VenueMode::cases(),
        ]);
    }
}
