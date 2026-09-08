<x-layouts.public :title="$event->name . ' — Ticket'">
    <article class="mx-auto max-w-md space-y-6">
        @if ($registration->status === \App\Enums\RegistrationStatus::Cancelled)
            <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                This registration was cancelled. This ticket is no longer valid.
            </div>
        @endif

        <header class="space-y-1">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $event->name }}</h1>
            <p class="text-sm text-slate-600">Attendance ticket</p>
        </header>

        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <p class="text-sm font-semibold text-slate-900">{{ $registration->attendee_name }}</p>
            @if ($registration->ticketType)
                <p class="mt-0.5 text-sm text-slate-600">{{ $registration->ticketType->name }}</p>
            @endif

            <div class="mt-5 flex justify-center">
                <div class="rounded-lg border border-slate-200 p-3">
                    {!! $qrCodeSvg !!}
                </div>
            </div>

            <dl class="mt-5 space-y-2 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-500">Ticket ID</dt>
                    <dd class="font-mono text-slate-900">{{ $ticket->ticket_code }}</dd>
                </div>
                @if ($event->start_at)
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Event date</dt>
                        <dd class="text-slate-900">
                            {{ \Illuminate\Support\Carbon::parse($event->start_at)->setTimezone($event->organization?->timezone ?? 'UTC')->format('M j, Y g:i A') }}
                        </dd>
                    </div>
                @endif
                @if ($venue = $event->venue?->is_public ? $event->venue : null)
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Venue</dt>
                        <dd class="text-right text-slate-900">
                            {{ $venue->name }}
                            @if ($venue->address)
                                <br />{{ $venue->address }}
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        </article>
</x-layouts.public>