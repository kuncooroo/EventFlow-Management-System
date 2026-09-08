<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateReminderSettings
{
    public function handle(Event $event, User $actor, array $data): Event
    {
        Gate::forUser($actor)->authorize('update', $event);

        $this->validate($data);

        $enabled = (bool) $data['reminder_enabled'];
        $hours = $this->normalizeHours($data['reminder_hours_before'] ?? null);

        if ($enabled && $hours === null) {
            throw ValidationException::withMessages([
                'reminder_hours_before' => 'Choose how many hours before the event starts the reminder should be sent.',
            ]);
        }

        $event->update([
            'reminder_enabled' => $enabled,
            'reminder_hours_before' => $hours,
        ]);

        return $event->refresh();
    }

    /**
     * @throws ValidationException
     */
    private function validate(array $data): void
    {
        $hours = $data['reminder_hours_before'] ?? null;

        if ($hours === null || $hours === '') {
            return;
        }

        if (! is_numeric($hours)) {
            throw ValidationException::withMessages([
                'reminder_hours_before' => 'The reminder offset must be a whole number of hours.',
            ]);
        }

        $hours = (int) $hours;

        if ($hours < 1 || $hours > 720) {
            throw ValidationException::withMessages([
                'reminder_hours_before' => 'The reminder offset must be between 1 and 720 hours.',
            ]);
        }
    }

    private function normalizeHours(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
