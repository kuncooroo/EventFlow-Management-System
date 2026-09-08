<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateRegistrationSettings
{
    public function handle(Event $event, User $actor, array $data): Event
    {
        Gate::forUser($actor)->authorize('update', $event);

        $this->validate($data);

        $event->update([
            'registration_enabled' => (bool) $data['registration_enabled'],
            'registration_starts_at' => $this->normalizeNullable($data['registration_starts_at'] ?? null),
            'registration_ends_at' => $this->normalizeNullable($data['registration_ends_at'] ?? null),
            'capacity' => $this->normalizeCapacity($data['capacity'] ?? null),
            'require_phone' => (bool) $data['require_phone'],
            'require_organization' => (bool) $data['require_organization'],
        ]);

        return $event->refresh();
    }

    /**
     * @throws ValidationException
     */
    private function validate(array $data): void
    {
        $start = $data['registration_starts_at'] ?? null;
        $end = $data['registration_ends_at'] ?? null;

        if ($start !== null && $start !== '' && ! $this->isValidDateTime($start)) {
            throw ValidationException::withMessages([
                'registration_starts_at' => 'The registration start time is invalid.',
            ]);
        }

        if ($end !== null && $end !== '' && ! $this->isValidDateTime($end)) {
            throw ValidationException::withMessages([
                'registration_ends_at' => 'The registration end time is invalid.',
            ]);
        }

        if ($start !== null && $start !== '' && $end !== null && $end !== '') {
            if (Carbon::parse($end)->lt(Carbon::parse($start))) {
                throw ValidationException::withMessages([
                    'registration_ends_at' => 'The registration end time must be on or after the start time.',
                ]);
            }
        }

        $capacity = $data['capacity'] ?? null;

        if ($capacity !== null && $capacity !== '' && (int) $capacity < 0) {
            throw ValidationException::withMessages([
                'capacity' => 'Capacity must be a non-negative number.',
            ]);
        }
    }

    private function isValidDateTime(mixed $value): bool
    {
        try {
            Carbon::parse($value);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function normalizeNullable(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : $value;
    }

    private function normalizeCapacity(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
