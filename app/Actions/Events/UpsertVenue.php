<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Enums\VenueMode;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpsertVenue
{
    public function handle(Event $event, User $actor, array $data): ?Venue
    {
        Gate::forUser($actor)->authorize('update', $event);

        if ($event->status !== EventStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only Draft events can have their venue configured.',
            ]);
        }

        $this->validate($data);

        $mode = $data['mode'];

        $event->update(['mode' => $mode]);

        if ($mode === VenueMode::Online->value) {
            $event->venue()?->delete();

            return null;
        }

        return $event->venue()->updateOrCreate(
            ['event_id' => $event->id],
            [
                'name' => $data['name'],
                'address' => isset($data['address']) && $data['address'] !== '' ? $data['address'] : null,
                'notes' => isset($data['notes']) && $data['notes'] !== '' ? $data['notes'] : null,
                'is_public' => (bool) ($data['is_public'] ?? true),
            ]
        );
    }

    /**
     * @throws ValidationException
     */
    private function validate(array $data): void
    {
        $mode = $data['mode'] ?? null;

        if (! in_array($mode, array_column(VenueMode::cases(), 'value'), true)) {
            throw ValidationException::withMessages([
                'mode' => 'The venue mode is required and must be online, offline, or hybrid.',
            ]);
        }

        if ($mode !== VenueMode::Online->value && empty(trim((string) ($data['name'] ?? '')))) {
            throw ValidationException::withMessages([
                'name' => 'The venue name is required for offline or hybrid events.',
            ]);
        }
    }
}
