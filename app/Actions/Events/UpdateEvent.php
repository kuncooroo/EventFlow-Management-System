<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateEvent
{
    public function handle(Event $event, User $actor, array $data): Event
    {
        Gate::forUser($actor)->authorize('update', $event);

        if ($event->status !== EventStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only Draft events can be updated via this action.',
            ]);
        }

        $this->validate($data);

        $event->update(array_filter([
            'name' => $data['name'] ?? $event->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $event->description,
            'organizer_name' => array_key_exists('organizer_name', $data) ? $data['organizer_name'] : $event->organizer_name,
            'mode' => array_key_exists('mode', $data) ? $data['mode'] : $event->mode,
            'start_at' => array_key_exists('start_at', $data) ? $data['start_at'] : $event->start_at,
            'end_at' => array_key_exists('end_at', $data) ? $data['end_at'] : $event->end_at,
            'capacity' => array_key_exists('capacity', $data) ? (isset($data['capacity']) ? (int) $data['capacity'] : null) : $event->capacity,
        ], fn ($v) => $v !== null || array_key_exists(array_search($v, $data, true), $data)));

        $event->refresh();

        return $event;
    }

    /**
     * @throws ValidationException
     */
    private function validate(array $data): void
    {
        if (isset($data['name']) && trim($data['name']) === '') {
            throw ValidationException::withMessages([
                'name' => 'The event name is required.',
            ]);
        }

        if (isset($data['start_at'], $data['end_at'])) {
            if ($data['end_at'] < $data['start_at']) {
                throw ValidationException::withMessages([
                    'end_at' => 'The end date must be on or after the start date.',
                ]);
            }
        }

        if (isset($data['capacity']) && (int) $data['capacity'] < 0) {
            throw ValidationException::withMessages([
                'capacity' => 'Capacity must be a non-negative number.',
            ]);
        }
    }
}
