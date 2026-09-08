<?php

namespace App\Actions\Events;

use App\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class UpdateTicketType
{
    use ValidatesTicketType;

    public function handle(TicketType $ticketType, User $actor, array $data): TicketType
    {
        Gate::forUser($actor)->authorize('update', $ticketType->event);

        $this->validateTicketType($data);
        $this->validateNameUnique($ticketType->event, $ticketType->id, $data['name']);

        $ticketType->update([
            'name' => trim($data['name']),
            'description' => $this->normalizeNullable($data['description'] ?? null),
            'price_amount' => (float) $data['price_amount'],
            'currency' => $this->normalizeCurrency($data['currency'] ?? null),
            'capacity' => $this->normalizeCapacity($data['capacity'] ?? null),
            'available_from' => $this->normalizeNullable($data['available_from'] ?? null),
            'available_until' => $this->normalizeNullable($data['available_until'] ?? null),
            'is_active' => (bool) ($data['is_active'] ?? $ticketType->is_active),
            'sort_order' => $ticketType->sort_order,
        ]);

        return $ticketType->refresh();
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
