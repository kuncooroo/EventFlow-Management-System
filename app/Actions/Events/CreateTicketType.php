<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CreateTicketType
{
    use ValidatesTicketType;

    public function handle(Event $event, User $actor, array $data): TicketType
    {
        Gate::forUser($actor)->authorize('update', $event);

        $this->validateTicketType($data);
        $this->validateNameUnique($event, null, $data['name']);

        $maxSortOrder = $event->ticketTypes()->max('sort_order') ?? 0;

        return $event->ticketTypes()->create([
            'name' => trim($data['name']),
            'description' => $this->normalizeNullable($data['description'] ?? null),
            'price_amount' => (float) $data['price_amount'],
            'currency' => $this->normalizeCurrency($data['currency'] ?? null),
            'capacity' => $this->normalizeCapacity($data['capacity'] ?? null),
            'available_from' => $this->normalizeNullable($data['available_from'] ?? null),
            'available_until' => $this->normalizeNullable($data['available_until'] ?? null),
            'is_active' => true,
            'sort_order' => $maxSortOrder + 1,
        ]);
    }

    private function normalizeCurrency(?string $currency): ?string
    {
        return $this->normalizeNullable($currency) === null
            ? null
            : Str::upper(trim($currency));
    }

    private function normalizeCapacity(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
