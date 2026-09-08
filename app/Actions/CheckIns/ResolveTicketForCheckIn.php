<?php

namespace App\Actions\CheckIns;

use App\Models\Event;
use App\Models\Ticket;

class ResolveTicketForCheckIn
{
    /**
     * Resolve a scanned QR token to a ticket that belongs to the given event.
     *
     * Returns null for unknown tokens, malformed input, and tickets that
     * belong to another event, so no attendance record can be created.
     */
    public function handle(Event $event, string $qrToken): ?Ticket
    {
        $token = trim($qrToken);

        if ($token === '' || preg_match('/^[A-Za-z0-9]+$/', $token) !== 1) {
            return null;
        }

        return Ticket::query()
            ->where('qr_token', $token)
            ->whereHas('registration', fn ($query) => $query->where('event_id', $event->id))
            ->first();
    }
}
