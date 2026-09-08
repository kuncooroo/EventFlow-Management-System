<?php

namespace App\Actions\Events;

use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

trait ValidatesTicketType
{
    /**
     * @throws ValidationException
     */
    private function validateTicketType(array $data): void
    {
        $name = $data['name'] ?? null;
        $price = $data['price_amount'] ?? 0;
        $currency = $data['currency'] ?? null;
        $capacity = $data['capacity'] ?? null;
        $from = $data['available_from'] ?? null;
        $until = $data['available_until'] ?? null;

        if ($name === null || trim($name) === '') {
            throw ValidationException::withMessages([
                'name' => 'The ticket type name is required.',
            ]);
        }

        if (! is_numeric($price) || (float) $price < 0) {
            throw ValidationException::withMessages([
                'price_amount' => 'The price must be a non-negative number.',
            ]);
        }

        if ((float) $price > 0 && ($currency === null || trim($currency) === '')) {
            throw ValidationException::withMessages([
                'currency' => 'A currency code is required when a price is set.',
            ]);
        }

        if ($currency !== null && trim($currency) !== '' && ! preg_match('/^[A-Za-z]{3}$/', trim($currency))) {
            throw ValidationException::withMessages([
                'currency' => 'The currency must be a 3-letter code (e.g. USD).',
            ]);
        }

        if ($capacity !== null && $capacity !== '' && (int) $capacity < 0) {
            throw ValidationException::withMessages([
                'capacity' => 'Capacity must be a non-negative number.',
            ]);
        }

        if ($from !== null && $from !== '' && ! $this->isValidDateTime($from)) {
            throw ValidationException::withMessages([
                'available_from' => 'The availability start is invalid.',
            ]);
        }

        if ($until !== null && $until !== '' && ! $this->isValidDateTime($until)) {
            throw ValidationException::withMessages([
                'available_until' => 'The availability end is invalid.',
            ]);
        }

        if ($from !== null && $from !== '' && $until !== null && $until !== '') {
            if (Carbon::parse($until)->lt(Carbon::parse($from))) {
                throw ValidationException::withMessages([
                    'available_until' => 'The availability end must be on or after the start.',
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

    /**
     * @throws ValidationException
     */
    private function validateNameUnique(Event $event, ?int $ignoreId, string $name): void
    {
        $exists = $event->ticketTypes()
            ->where('name', trim($name))
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'A ticket type with this name already exists for this event.',
            ]);
        }
    }

    private function normalizeNullable(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : $value;
    }
}
