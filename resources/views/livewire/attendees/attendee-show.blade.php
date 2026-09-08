<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('app.events.attendees.index', $event) }}" class="text-sm text-teal-600 hover:text-teal-800">&larr; Back to attendees</a>
            <h2 class="mt-1 text-lg font-semibold text-slate-900">{{ $registration->attendee_name }}</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $registration->attendee_email }}</p>
        </div>
        <div class="flex items-center gap-2">
            @include('livewire.attendees.partials.status-badge', ['registration' => $registration])
        </div>
    </div>

    {{-- Registration --}}
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Registration</h3>
        <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <dt class="text-sm font-medium text-slate-500">Registration ID</dt>
                <dd class="mt-1 font-mono text-sm text-slate-900">{{ $registration->registration_code }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Ticket Type</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $registration->ticketType?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Registered At</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $registration->registered_at?->format('M j, Y g:i A') ?? '—' }}</dd>
            </div>
            @if ($registration->status->value === 'cancelled')
                <div>
                    <dt class="text-sm font-medium text-slate-500">Cancelled At</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $registration->cancelled_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Cancelled By</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $registration->cancelledBy?->name ?? '—' }}</dd>
                </div>
            @endif
        </dl>
    </div>

    {{-- Attendance --}}
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Attendance</h3>
        <div class="mt-4">
            @include('livewire.attendees.partials.checkin-badge', ['registration' => (object) ['is_checked_in' => $isCheckedIn]])
            <livewire:attendees.check-in-action :event="$event" :registration="$registration" />
        </div>
    </div>

    {{-- Answers --}}
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Registration Answers</h3>
        @if ($registration->answers->isEmpty())
            <p class="mt-4 text-sm text-slate-500">No additional answers were provided.</p>
        @else
            <dl class="mt-4 space-y-4">
                @foreach ($registration->answers as $answer)
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ $answer->field_label_snapshot }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            @if (is_array($answer->answer_json))
                                {{ implode(', ', $answer->answer_json) }}
                            @elseif ($answer->answer_text !== null)
                                {{ $answer->answer_text }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </div>

    {{-- Ticket --}}
    @if ($registration->ticket)
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Ticket</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $registration->ticket->ticket_code }}</p>
                </div>
                <a href="{{ route('tickets.public.show', $registration->ticket->ticket_code) }}"
                   target="_blank"
                   class="inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                    View Ticket
                </a>
            </div>
        </div>
    @endif

    {{-- Administrative actions --}}
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <livewire:attendees.cancel-registration-action :event="$event" :registration="$registration" />
    </div>
</div>