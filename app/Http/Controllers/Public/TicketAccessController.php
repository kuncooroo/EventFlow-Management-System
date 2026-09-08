<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\View\View;

class TicketAccessController extends Controller
{
    public function show(string $token, TicketQrCodeService $qrCodes): View
    {
        if (preg_match('/^[A-Za-z0-9]{26}$/', $token) !== 1) {
            abort(404);
        }

        $ticket = Ticket::query()
            ->where('ticket_code', $token)
            ->with(['registration.event', 'registration.ticketType'])
            ->first();

        if ($ticket === null) {
            abort(404);
        }

        return view('public.ticket', [
            'ticket' => $ticket,
            'registration' => $ticket->registration,
            'event' => $ticket->registration->event,
            'qrCodeSvg' => $qrCodes->svg($ticket->qr_token),
        ]);
    }
}
