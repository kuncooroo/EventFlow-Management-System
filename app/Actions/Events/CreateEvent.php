<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateEvent
{
    public function handle(
        Organization $organization,
        User $actor,
        string $name,
        array $data = [],
    ): Event {
        Gate::forUser($actor)->authorize('create', Event::class);

        $this->validate($data, $name);

        return $organization->events()->create([
            'created_by_user_id' => $actor->id,
            'name' => $name,
            'status' => EventStatus::Draft,
            'description' => $data['description'] ?? null,
            'organizer_name' => $data['organizer_name'] ?? null,
            'mode' => $data['mode'] ?? null,
            'start_at' => $data['start_at'] ?? null,
            'end_at' => $data['end_at'] ?? null,
            'capacity' => isset($data['capacity']) ? (int) $data['capacity'] : null,
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function validate(array $data, string $name): void
    {
        if (trim($name) === '') {
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
