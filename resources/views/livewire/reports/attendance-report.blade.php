<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Attendance Report</h2>
            <p class="mt-1 text-sm text-slate-600">Confirmed, checked-in, and attendance percentage per event (FR-RPT-002, FR-RPT-004).</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('app.reports.export', ['type' => 'attendance']) }}"
               class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Export CSV
            </a>
            @if ($filtersActive)
                <a href="{{ route('app.reports.export', array_filter([
                        'type' => 'attendance',
                        'event_id' => $eventFilter,
                        'registered_from' => $dateFrom,
                        'registered_to' => $dateTo,
                    ])) }}"
                   class="rounded-md bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700">
                    Export filtered
                </a>
            @endif
        </div>
    </div>

    <x-report-nav active="attendance" />

    {{-- Filters --}}
    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="eventFilter" class="block text-xs font-medium text-slate-500">Event</label>
                <select id="eventFilter" wire:model="eventFilter" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    <option value="">All events</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}">{{ $event->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dateFrom" class="block text-xs font-medium text-slate-500">Registered from</label>
                <input id="dateFrom" type="date" wire:model="dateFrom" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
            </div>
            <div>
                <label for="dateTo" class="block text-xs font-medium text-slate-500">Registered to</label>
                <input id="dateTo" type="date" wire:model="dateTo" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
            </div>
        </div>
        @if ($filtersActive)
            <div class="mt-3 flex justify-end">
                <button type="button" wire:click="clearFilters" class="text-sm font-medium text-teal-600 hover:text-teal-700">
                    Clear filters
                </button>
            </div>
        @endif
    </div>

    {{-- Summary cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Registrations</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $totalRegistrations }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Confirmed</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $totalConfirmed }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Checked In</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $totalCheckedIn }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Attendance</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $attendancePercentage ?? 'Not available' }}@if ($attendancePercentage !== null)%@endif</p>
        </div>
    </div>

    {{-- Rows --}}
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">{{ $eventFilter === '' ? 'By Event' : 'By Ticket Type' }}</h3>
        </div>
        @if (count($rows) === 0)
            <div class="px-5 py-4">
                <x-empty-state title="No attendance data" description="Records will appear here once attendees register." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">{{ $eventFilter === '' ? 'Event' : 'Ticket Type' }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Registrations</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Confirmed</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Checked In</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Attendance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($rows as $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-900">
                                        {{ $eventFilter === '' ? $row['event'] : ($row['ticket_type'] ?? 'No ticket type') }}
                                    </p>
                                    @if ($eventFilter === '')
                                        <p class="text-xs text-slate-500">{{ $row['event'] }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $row['registrations'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $row['confirmed'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $row['checked_in'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">
                                    {{ $row['attendance_percentage'] ?? 'Not available' }}@if ($row['attendance_percentage'] !== null)%@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>