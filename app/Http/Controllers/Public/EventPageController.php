<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Queries\Public\PublicEventQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class EventPageController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $query = new PublicEventQuery;

        $event = $query->resolve($slug);

        if ($event === null) {
            abort(404);
        }

        $timezone = $event->organization?->timezone ?? 'UTC';

        return view('public.event-show', [
            'event' => $event,
            'cta' => $query->ctaState($event),
            'ticketTypes' => $query->availableTicketTypes($event),
            'timezone' => $timezone,
            'banner' => $event->banner(),
            'venue' => $event->venue?->is_public ? $event->venue : null,
            'agendaItems' => $event->agendaItems()->ordered()->get(),
            'startLabel' => $this->formatDate($event->start_at, $timezone),
            'endLabel' => $this->formatDate($event->end_at, $timezone),
        ]);
    }

    private function formatDate(?\DateTimeInterface $date, string $timezone): ?string
    {
        if ($date === null) {
            return null;
        }

        return Carbon::parse($date)->setTimezone($timezone)->format('M j, Y g:i A');
    }
}
