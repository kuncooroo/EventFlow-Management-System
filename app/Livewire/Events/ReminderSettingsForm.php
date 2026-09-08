<?php

namespace App\Livewire\Events;

use App\Actions\Events\UpdateReminderSettings;
use App\Models\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ReminderSettingsForm extends Component
{
    public Event $event;

    public bool $reminder_enabled = false;

    public string $reminder_hours_before = '';

    public function mount(Event $event): void
    {
        $this->event = $event;

        Gate::authorize('update', $this->event);

        $this->reminder_enabled = $this->event->reminder_enabled;
        $this->reminder_hours_before = $this->event->reminder_hours_before ?? '';
    }

    public function save(UpdateReminderSettings $action): void
    {
        Gate::authorize('update', $this->event);

        $this->validateForm();

        try {
            $this->event = $action->handle($this->event, auth()->user(), [
                'reminder_enabled' => $this->reminder_enabled,
                'reminder_hours_before' => $this->reminder_hours_before,
            ]);

            session()->flash('status', 'Reminder settings saved.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        }
    }

    public function render()
    {
        return view('livewire.events.reminder-settings-form', [
            'timezone' => $this->event->organization?->timezone ?? 'UTC',
        ]);
    }

    private function validateForm(): void
    {
        $this->validate([
            'reminder_enabled' => 'boolean',
            'reminder_hours_before' => 'nullable|integer|min:1|max:720',
        ]);
    }
}
