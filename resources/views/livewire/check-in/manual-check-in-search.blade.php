<div class="space-y-6">

    {{-- Header --}}
    <div>
        <div class="flex items-center gap-2 text-sm text-slate-500">
            <a href="{{ route('app.events.index') }}" class="text-teal-600 hover:text-teal-800">Events</a>
            <span>/</span>
            <a href="{{ route('app.events.setup', $event) }}" class="text-teal-600 hover:text-teal-800">{{ $event->name }}</a>
            <span>/</span>
            <span class="text-slate-700">Check-In</span>
        </div>
        <h2 class="mt-1 text-lg font-semibold text-slate-900">Check-In</h2>
        <p class="mt-1 text-sm text-slate-600">Scan a ticket QR or search for an attendee to check in.</p>
    </div>

    {{-- QR scanner (TASK-023) with non-camera fallback --}}
    <section aria-labelledby="scan-heading">
        <h3 id="scan-heading" class="sr-only">Scan a ticket</h3>
        <livewire:check-in.check-in-scanner :event="$event" :key="'scanner-'.$event->id" />
    </section>

    {{-- Manual search (TASK-022) --}}
    <section aria-labelledby="manual-heading" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="flex-1">
                <h3 id="manual-heading" class="text-sm font-semibold text-slate-900">Manual Check-In</h3>
                <p class="mt-1 text-sm text-slate-600">Search by name, email, registration code, or ticket code.</p>
                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Search attendees…"
                    autofocus
                    class="mt-3 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                />
            </div>
        </div>

        @if ($resultOutcome)
            <div class="mt-4">
                <div class="flex flex-wrap items-start gap-3">
                    <div class="min-w-0 flex-1">
                        @include('livewire.check-in.partials.result-card', [
                            'outcome' => $resultOutcome,
                            'title' => $resultTitle,
                            'detail' => $resultDetail,
                        ])
                    </div>
                    <button
                        wire:click="clearResult"
                        class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                        Dismiss
                    </button>
                </div>
            </div>
        @endif

        <div class="mt-4">
            @if (! $hasSearched)
                <p class="rounded-md border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                    Start typing to find an attendee.
                </p>
            @else
                <div class="space-y-2">
                    @foreach ($attendees as $registration)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-slate-200 px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-slate-900">{{ $registration->attendee_name }}</p>
                                <p class="truncate text-sm text-slate-600">{{ $registration->attendee_email }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $registration->ticketType?->name ?? 'No ticket type' }} ·
                                    {{ $registration->registration_code }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                @include('livewire.attendees.partials.status-badge', ['registration' => $registration])
                                @include('livewire.attendees.partials.checkin-badge', ['registration' => $registration])
                                @if ($registration->status->value === 'confirmed' && ! $registration->is_checked_in)
                                    <button
                                        wire:click="checkIn({{ $registration->id }})"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                                        Check In
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @empty($attendees->items())
                        <p class="rounded-md border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                            No attendees match your search.
                        </p>
                    @endempty
                </div>

                @if ($attendees->hasPages())
                    <div class="pt-3">
                        {{ $attendees->links() }}
                    </div>
                @endif
            @endif
        </div>
    </section>
</div>