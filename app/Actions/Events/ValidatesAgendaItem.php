<?php

namespace App\Actions\Events;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

trait ValidatesAgendaItem
{
    /**
     * @throws ValidationException
     */
    private function validateAgendaItem(array $data): void
    {
        $title = $data['title'] ?? null;
        $startAt = $data['start_at'] ?? null;
        $endAt = $data['end_at'] ?? null;

        if ($title === null || trim($title) === '') {
            throw ValidationException::withMessages([
                'title' => 'The agenda item title is required.',
            ]);
        }

        if ($startAt === null || $startAt === '' || ! $this->isValidDateTime($startAt)) {
            throw ValidationException::withMessages([
                'start_at' => 'The scheduled start time is required.',
            ]);
        }

        if ($endAt !== null && $endAt !== '') {
            if (! $this->isValidDateTime($endAt)) {
                throw ValidationException::withMessages([
                    'end_at' => 'The end time is invalid.',
                ]);
            }

            if (Carbon::parse($endAt)->lt(Carbon::parse($startAt))) {
                throw ValidationException::withMessages([
                    'end_at' => 'The end time must be on or after the start time.',
                ]);
            }
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
}
