<?php

namespace App\Actions\Tickets;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use App\Models\Ticket;
use Illuminate\Validation\ValidationException;

class IssueTicket
{
    public function handle(Registration $registration): Ticket
    {
        if ($registration->status !== RegistrationStatus::Confirmed) {
            throw ValidationException::withMessages([
                'registration' => 'A ticket can only be issued for a confirmed registration.',
            ]);
        }

        $existing = Ticket::query()
            ->where('registration_id', $registration->id)
            ->first();

        if ($existing instanceof Ticket) {
            $registration->setRelation('ticket', $existing);

            return $existing;
        }

        $ticket = $this->createTicket($registration);
        $registration->setRelation('ticket', $ticket);

        return $ticket;
    }

    private function createTicket(Registration $registration): Ticket
    {
        do {
            $ticket = [
                'ticket_code' => bin2hex(random_bytes(13)),
                'qr_token' => bin2hex(random_bytes(32)),
            ];

            $collides = Ticket::query()
                ->where('ticket_code', $ticket['ticket_code'])
                ->orWhere('qr_token', $ticket['qr_token'])
                ->exists();
        } while ($collides);

        return Ticket::create([
            'registration_id' => $registration->id,
            'ticket_code' => $ticket['ticket_code'],
            'qr_token' => $ticket['qr_token'],
            'issued_at' => now(),
        ]);
    }
}
