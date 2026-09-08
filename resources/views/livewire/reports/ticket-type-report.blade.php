<div class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Ticket Type Report</h2>
        <p class="mt-1 text-sm text-slate-600">Registration and check-in totals by ticket type (FR-RPT-003).</p>
    </div>

    <x-report-nav active="ticket-types" />

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

    {{-- Totals --}}
    <div class="grid gap-4 sm:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Registrations</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $totals['registrations'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Confirmed</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $totals['confirmed'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Cancelled</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $totals['cancelled'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Checked In</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $totals['checked_in'] }}</p>
        </div>
    </div>

    {{-- Rows --}}
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Ticket Types</h3>
        </div>
        @if (count($rows) === 0)
            <div class="px-5 py-4">
                <x-empty-state title="No ticket types" description="Configure ticket types to see report data." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Type</th>
                            @if ($eventFilter === '')
                                <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Event</th>
                            @endif
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Status</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Capacity</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Registered</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Confirmed</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Cancelled</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Checked In</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($rows as $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $row['name'] }}</td>
                                @if ($eventFilter === '')
                                    <td class="px-4 py-3 text-slate-600">{{ $row['event'] }}</td>
                                @endif
                                <td class="px-4 py-3 text-slate-600">{{ $row['is_active'] ? 'Active' : 'Inactive' }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $row['capacity'] ?? 'Unlimited' }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $row['registrations'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $row['confirmed'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $row['cancelled'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $row['checked_in'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>