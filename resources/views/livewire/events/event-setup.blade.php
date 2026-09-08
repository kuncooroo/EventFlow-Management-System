<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('app.events.index') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Back to Events</a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h2 class="text-lg font-semibold text-slate-900">{{ $event->name }}</h2>
                @php $color = $event->status->badgeColor() @endphp
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                    @if($color === 'slate') bg-slate-100 text-slate-700
                    @elseif($color === 'teal') bg-teal-100 text-teal-700
                    @elseif($color === 'blue') bg-blue-100 text-blue-700
                    @elseif($color === 'green') bg-green-100 text-green-700
                    @elseif($color === 'red') bg-red-100 text-red-700
                    @else bg-amber-100 text-amber-700
                    @endif">
                    {{ $event->status->label() }}
                </span>
            </div>
        </div>
        @if ($canUpdate)
            <a href="{{ route('app.events.edit', $event) }}" class="inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                Edit Event
            </a>
        @endif
    </div>

    {{-- Setup tabs / future config shells --}}
    <nav class="flex gap-1 overflow-x-auto rounded-lg border border-slate-200 bg-white p-1 text-sm shadow-sm" aria-label="Event configuration">
        <span class="whitespace-nowrap rounded-md bg-teal-50 px-3 py-1.5 font-medium text-teal-700">Setup</span>
        @if ($canUpdate)
            <a href="{{ route('app.events.edit', $event) }}" class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Details</a>
        @else
            <span class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-400" title="Only authorized members can edit this event">Details</span>
        @endif
        @if ($canUpdate)
            <a href="#banner" class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Banner</a>
        @else
            <span class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-400" title="Only authorized members can configure this event">Banner</span>
        @endif
        @if ($canUpdate)
            <a href="#venue" class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Venue</a>
        @else
            <span class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-400" title="Only authorized members can configure this event">Venue</span>
        @endif
        @if ($canUpdate)
            <a href="#agenda" class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Agenda</a>
        @else
            <span class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-400" title="Only authorized members can configure this event">Agenda</span>
        @endif
        @if ($canUpdate)
            <a href="#registration" class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Registration</a>
        @else
            <span class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-400" title="Only authorized members can configure this event">Registration</span>
        @endif
        @if ($canUpdate)
            <a href="#reminders" class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Reminders</a>
        @else
            <span class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-400" title="Only authorized members can configure this event">Reminders</span>
        @endif
        @if ($canUpdate)
            <a href="#custom-fields" class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Custom Fields</a>
        @else
            <span class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-400" title="Only authorized members can configure this event">Custom Fields</span>
        @endif
        @if ($canUpdate)
            <a href="#ticket-types" class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Ticket Types</a>
        @else
            <span class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-400" title="Only authorized members can configure this event">Ticket Types</span>
        @endif
        @if ($canPublish)
            <a href="#publish" class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Publish</a>
        @else
            <span class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-400" title="Only authorized members can publish this event">Publish</span>
        @endif
        <a href="{{ route('app.events.attendees.index', $event) }}" class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Attendees</a>
        @if ($canView)
            <a href="{{ route('app.events.dashboard', $event) }}" class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Dashboard</a>
        @endif
        @if ($canCheckIn)
            <a href="{{ route('app.events.check-in.index', $event) }}" class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Check-In</a>
        @else
            <span class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-400" title="Only assigned operators can check in attendees">Check-In</span>
        @endif
        @if ($canManageLifecycle)
            <a href="#lifecycle" class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Lifecycle</a>
        @else
            <span class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-400" title="Only authorized members can change this event's lifecycle">Lifecycle</span>
        @endif
        @if ($canView)
            <a href="{{ route('app.reports.registrations', ['event_id' => $event->id]) }}" class="whitespace-nowrap rounded-md px-3 py-1.5 text-slate-600 hover:bg-slate-100">Reports</a>
        @endif
    </nav>

    @if (session('status'))
        <div class="rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Publish (authorized members) --}}
    @if ($canPublish)
        <section id="publish" class="overflow-hidden rounded-lg">
            <livewire:events.publish-event-button :event="$event" :key="'publish-'.$event->id" />
        </section>
    @endif

    {{-- Lifecycle transitions (authorized members) --}}
    @if ($canManageLifecycle)
        <section id="lifecycle" class="overflow-hidden rounded-lg">
            <livewire:events.lifecycle-actions :event="$event" :key="'lifecycle-'.$event->id" />
        </section>
    @endif

    {{-- Event details summary --}}
    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Event Details</h3>
            <p class="mt-1 text-sm text-slate-600">Current draft configuration for this event.</p>
        </div>
        <dl class="grid grid-cols-1 gap-x-6 gap-y-4 px-5 py-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="font-medium text-slate-500">Mode</dt>
                <dd class="mt-0.5 capitalize text-slate-900">{{ $event->mode ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-500">Capacity</dt>
                <dd class="mt-0.5 text-slate-900">{{ $event->capacity ?? 'Unlimited' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-500">Starts</dt>
                <dd class="mt-0.5 text-slate-900">{{ $event->start_at?->format('M j, Y g:i A') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-500">Ends</dt>
                <dd class="mt-0.5 text-slate-900">{{ $event->end_at?->format('M j, Y g:i A') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-500">Organizer</dt>
                <dd class="mt-0.5 text-slate-900">{{ $event->organizer_name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-500">Created By</dt>
                <dd class="mt-0.5 text-slate-900">{{ $event->createdBy?->name ?? '—' }}</dd>
            </div>
        </dl>
    </section>

    {{-- Banner (authorized members) --}}
    @if ($canUpdate)
        <section id="banner" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <livewire:events.banner-upload :event="$event" :key="'banner-'.$event->id" />
        </section>
    @endif

    {{-- Venue (authorized members) --}}
    @if ($canUpdate)
        <section id="venue" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <livewire:events.venue-form :event="$event" :key="'venue-'.$event->id" />
        </section>
    @endif

    {{-- Agenda (authorized members) --}}
    @if ($canUpdate)
        <section id="agenda" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <livewire:events.agenda-manager :event="$event" :key="'agenda-'.$event->id" />
        </section>
    @endif

    {{-- Registration settings (authorized members) --}}
    @if ($canUpdate)
        <section id="registration" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <livewire:events.registration-settings-form :event="$event" :key="'registration-'.$event->id" />
        </section>
    @endif

    {{-- Reminder settings (authorized members) --}}
    @if ($canUpdate)
        <section id="reminders" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <livewire:events.reminder-settings-form :event="$event" :key="'reminders-'.$event->id" />
        </section>
    @endif

    {{-- Custom fields (authorized members) --}}
    @if ($canUpdate)
        <section id="custom-fields" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <livewire:events.registration-field-manager :event="$event" :key="'fields-'.$event->id" />
        </section>
    @endif

    {{-- Ticket types (authorized members) --}}
    @if ($canUpdate)
        <section id="ticket-types" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <livewire:events.ticket-type-manager :event="$event" :key="'tickets-'.$event->id" />
        </section>
    @endif

    {{-- Assignments (owners/admins only) --}}
    @if ($canManageAssignments)
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <livewire:events.assignment-manager :event="$event" :key="'assignments-'.$event->id" />
        </section>
    @endif
</div>