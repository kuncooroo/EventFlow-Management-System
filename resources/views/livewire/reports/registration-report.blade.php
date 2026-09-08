<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Registration Report</h2>
            <p class="mt-1 text-sm text-slate-600">Totals for {{ $organization->name }} reconcile with the underlying records (FR-RPT-001).</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('app.reports.export', ['type' => 'registrations']) }}"
               class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Export CSV
            </a>
            @if ($filtersActive)
                <a href="{{ route('app.reports.export', array_filter([
                        'type' => 'registrations',
                        'event_id' => $eventFilter,
                        'status' => $statusFilter,
                        'ticket_type_id' => $ticketTypeFilter,
                        'registered_from' => $dateFrom,
                        'registered_to' => $dateTo,
                    ])) }}"
                   class="rounded-md bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700">
                    Export filtered
                </a>
            @endif
        </div>
    </div>

    <x-report-nav active="registrations" />

    {{-- Filters --}}
    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
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
                <label for="statusFilter" class="block text-xs font-medium text-slate-500">Status</label>
                <select id="statusFilter" wire:model="statusFilter" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    <option value="">All statuses</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            @if ($ticketTypes->isNotEmpty())
                <div>
                    <label for="ticketTypeFilter" class="block text-xs font-medium text-slate-500">Ticket Type</label>
                    <select id="ticketTypeFilter" wire:model="ticketTypeFilter" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                        <option value="">All types</option>
                        @foreach ($ticketTypes as $ticketType)
                            <option value="{{ $ticketType->id }}">{{ $ticketType->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
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
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Registrations</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $total }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Confirmed</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $confirmed }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Cancelled</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $cancelled }}</p>
        </div>
    </div>

    {{-- Ticket type breakdown --}}
    @if (count($byTicketType) > 0)
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-900">By Ticket Type</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Type</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Registrations</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Confirmed</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Cancelled</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($byTicketType as $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $row['ticket_type'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $row['total'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $row['confirmed'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $row['cancelled'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Registrations --}}
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Registrations</h3>
        </div>
        @if ($registrations->isEmpty())
            <div class="px-5 py-4">
                <x-empty-state title="No registrations" description="Adjust filters to see more records." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Attendee</th>
                            @if (! $events->isEmpty() && $eventFilter === '')
                                <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Event</th>
                            @endif
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Ticket Type</th>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Status</th>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Registered</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($registrations as $registration)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-900">{{ $registration->attendee_name }}</p>
                                    <p class="text-xs text-slate-500">{{ $registration->attendee_email }}</p>
                                </td>
                                @if (! $events->isEmpty() && $eventFilter === '')
                                    <td class="px-4 py-3 text-slate-600">{{ $registration->event?->name ?? '—' }}</td>
                                @endif
                                <td class="px-4 py-3 text-slate-600">{{ $registration->ticketType?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $registration->status->label() }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $registration->registered_at?->format('M j, Y g:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-3">
                {{ $registrations->links() }}
            </div>
        @endif
    </div>
</div>