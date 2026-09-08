<?php

namespace App\Livewire\Events;

use App\Actions\Events\UpdateRegistrationSettings;
use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class RegistrationSettingsForm extends Component
{
    public Event $event;

    public bool $registration_enabled = false;

    public string $registration_starts_at = '';

    public string $registration_ends_at = '';

    public string $capacity = '';

    public bool $require_phone = false;

    public bool $require_organization = false;

    public function mount(Event $event): void
    {
        $this->event = $event;

        Gate::authorize('update', $this->event);

        $this->registration_enabled = $this->event->registration_enabled;
        $this->registration_starts_at = $this->event->registration_starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->registration_ends_at = $this->event->registration_ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->capacity = $this->event->capacity ?? '';
        $this->require_phone = $this->event->require_phone;
        $this->require_organization = $this->event->require_organization;
    }

    public function save(UpdateRegistrationSettings $action): void
    {
        Gate::authorize('update', $this->event);

        $this->validateForm();

        try {
            $this->event = $action->handle($this->event, auth()->user(), [
                'registration_enabled' => $this->registration_enabled,
                'registration_starts_at' => $this->registration_starts_at,
                'registration_ends_at' => $this->registration_ends_at,
                'capacity' => $this->capacity,
                'require_phone' => $this->require_phone,
                'require_organization' => $this->require_organization,
            ]);

            session()->flash('status', 'Registration settings saved.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        }
    }

    public function render()
    {
        return view('livewire.events.registration-settings-form', [
            'availability' => $this->availability(),
            'timezone' => $this->event->organization?->timezone ?? 'UTC',
        ]);
    }

    /**
     * @return array{label: string, tone: string, note: string}
     */
    private function availability(): array
    {
        $timezone = $this->event->organization?->timezone ?? 'UTC';

        if (! $this->event->registration_enabled) {
            return [
                'label' => 'Closed',
                'tone' => 'slate',
                'note' => 'Registration is disabled for this event.',
            ];
        }

        $startsAt = $this->event->registration_starts_at;
        $endsAt = $this->event->registration_ends_at;
        $now = Carbon::now();

        if ($startsAt !== null && $now->lt($startsAt)) {
            return [
                'label' => 'Scheduled',
                'tone' => 'amber',
                'note' => 'Registration opens on '.$startsAt->copy()->setTimezone($timezone)->format('M j, Y g:i A').'.',
            ];
        }

        if ($endsAt !== null && $now->gt($endsAt)) {
            return [
                'label' => 'Closed',
                'tone' => 'red',
                'note' => 'Registration closed on '.$endsAt->copy()->setTimezone($timezone)->format('M j, Y g:i A').'.',
            ];
        }

        if ($this->event->status !== EventStatus::Draft) {
            return [
                'label' => 'Open',
                'tone' => 'teal',
                'note' => 'Registration is open for this event.',
            ];
        }

        return [
            'label' => 'Configured',
            'tone' => 'teal',
            'note' => 'Registration is configured — Draft events are not public yet.',
        ];
    }

    private function validateForm(): void
    {
        $this->validate([
            'registration_enabled' => 'boolean',
            'registration_starts_at' => 'nullable',
            'registration_ends_at' => 'nullable',
            'capacity' => 'nullable|integer|min:0',
            'require_phone' => 'boolean',
            'require_organization' => 'boolean',
        ]);
    }
}
