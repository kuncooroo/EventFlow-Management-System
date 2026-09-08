<div class="space-y-6">
    {{-- Header --}}
    <div>
        <a href="{{ route('app.events.setup', $event) }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Back to Event</a>
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
        <p class="mt-1 text-sm text-slate-600">
            {{ $event->start_at?->format('M j, Y g:i A') ?? 'No start date' }}
            &middot; {{ $event->venue?->venue_name ?? $event->venue?->city ?? 'No venue' }}
        </p>
        <p class="mt-1 text-sm text-slate-600">
            Capacity: {{ $event->capacity ?? 'Unlimited' }}
            &middot; Registration {{ $event->registration_enabled ? 'Open' : 'Closed' }}
        </p>
    </div>

    {{-- KPI cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Registrations</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $totalRegistrations }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Confirmed</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $confirmedRegistrations }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Checked In</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $checkedIn }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Attendance</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $attendancePercentage ?? 'Not available' }}@if ($attendancePercentage !== null)%@endif</p>
        </div>
    </div>

    {{-- Ticket types --}}
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Ticket Types</h3>
        </div>
        @if ($ticketTypes->isEmpty())
            <div class="px-5 py-4">
                <x-empty-state title="No ticket types" description="Configure ticket types to accept registrations." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Type</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Registered</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Confirmed</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Checked In</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Capacity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($ticketTypes as $ticketType)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ $ticketType->name }}
                                    @unless ($ticketType->is_active)
                                        <span class="ml-2 text-xs text-slate-400">(inactive)</span>
                                    @endunless
                                </td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $ticketType->registrations_count }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $ticketType->confirmed_count }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $ticketType->checked_in_count }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $ticketType->capacity ?? 'Unlimited' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Recent registrations --}}
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-900">Recent Registrations</h3>
            </div>
            @if ($recentRegistrations->isEmpty())
                <div class="px-5 py-4">
                    <x-empty-state title="No registrations yet" description="Registrations will appear here as attendees sign up." />
                </div>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($recentRegistrations as $registration)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-slate-900">{{ $registration->attendee_name }}</p>
                                <p class="truncate text-slate-500">
                                    {{ $registration->ticketType?->name ?? 'No ticket type' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-slate-600">{{ $registration->status->label() }}</p>
                                <p class="text-xs text-slate-400">{{ $registration->registered_at?->format('M j, g:i A') }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Recent check-ins --}}
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-900">Recent Check-Ins</h3>
            </div>
            @if ($recentCheckIns->isEmpty())
                <div class="px-5 py-4">
                    <x-empty-state title="No check-ins yet" description="Checked-in attendees will appear here." />
                </div>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($recentCheckIns as $checkIn)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-slate-900">{{ $checkIn->registration->attendee_name }}</p>
                                <p class="truncate text-slate-500">
                                    {{ $checkIn->registration->ticketType?->name ?? 'No ticket type' }} &middot; {{ $checkIn->method->label() }}
                                </p>
                            </div>
                            <p class="text-xs text-slate-400">{{ $checkIn->checked_in_at?->format('M j, g:i A') }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>