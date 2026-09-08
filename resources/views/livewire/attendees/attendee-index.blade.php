<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <a href="{{ route('app.events.index') }}" class="text-teal-600 hover:text-teal-800">Events</a>
                <span>/</span>
                <a href="{{ route('app.events.setup', $event) }}" class="text-teal-600 hover:text-teal-800">{{ $event->name }}</a>
                <span>/</span>
                <span class="text-slate-700">Attendees</span>
            </div>
            <h2 class="mt-1 text-lg font-semibold text-slate-900">Attendees</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $attendees->total() }} total registration{{ $attendees->total() === 1 ? '' : 's' }}.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('app.reports.export', ['type' => 'attendees', 'event_id' => $event->id]) }}"
               class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Export CSV
            </a>
            @if ($filtersActive)
                <a href="{{ route('app.reports.export', array_filter([
                        'type' => 'attendees',
                        'event_id' => $event->id,
                        'search' => $search,
                        'status' => $statusFilter,
                        'ticket_type_id' => $ticketTypeFilter,
                        'checked_in' => $checkedInFilter,
                        'registered_from' => $dateFrom,
                        'registered_to' => $dateTo,
                    ])) }}"
                   class="rounded-md bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700">
                    Export filtered
                </a>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Search name, email, or code…"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                />
            </div>
            <select wire:model.live="statusFilter"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="ticketTypeFilter"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                <option value="">All ticket types</option>
                @foreach ($ticketTypes as $ticketType)
                    <option value="{{ $ticketType->id }}">{{ $ticketType->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="checkedInFilter"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                <option value="">Any check-in status</option>
                <option value="1">Checked in</option>
                <option value="0">Not checked in</option>
            </select>
            <div class="flex items-center gap-2">
                <input
                    wire:model.live="dateFrom"
                    type="date"
                    aria-label="Registered from"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                />
                <span class="text-slate-400">—</span>
                <input
                    wire:model.live="dateTo"
                    type="date"
                    aria-label="Registered to"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                />
            </div>
        </div>

        @if ($filtersActive)
            <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
                <button wire:click="clearFilters"
                        class="text-sm font-medium text-teal-600 hover:text-teal-800">
                    Clear filters
                </button>
                <span class="text-xs text-slate-500">{{ $attendees->total() }} result{{ $attendees->total() === 1 ? '' : 's' }}</span>
            </div>
        @endif
    </div>

    @empty($attendees->items())
        <div class="rounded-lg border border-slate-200 bg-white px-6 py-12 text-center shadow-sm">
            <p class="text-sm text-slate-500">
                {{ $filtersActive ? 'No attendees match your filters.' : 'No attendees yet.' }}
            </p>
            @if ($filtersActive)
                <button wire:click="clearFilters"
                        class="mt-3 text-sm font-medium text-teal-600 hover:text-teal-800">
                    Clear filters
                </button>
            @endif
        </div>
    @endempty

    {{-- Desktop table --}}
    <div class="hidden overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm md:block">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Name</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Email</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Ticket Type</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Status</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Check-In</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Registered</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($attendees as $registration)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $registration->attendee_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $registration->attendee_email }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $registration->ticketType?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @include('livewire.attendees.partials.status-badge', ['registration' => $registration])
                            </td>
                            <td class="px-4 py-3">
                                @include('livewire.attendees.partials.checkin-badge', ['registration' => $registration])
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $registration->registered_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('app.events.attendees.show', [$event, $registration]) }}"
                                   class="font-medium text-teal-600 hover:text-teal-800">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($attendees->hasPages())
            <div class="border-t border-slate-200 bg-slate-50 px-4 py-3">
                {{ $attendees->links() }}
            </div>
        @endif
    </div>

    {{-- Mobile cards --}}
    <div class="space-y-3 md:hidden">
        @foreach ($attendees as $registration)
            <a href="{{ route('app.events.attendees.show', [$event, $registration]) }}"
               class="block rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-slate-900">{{ $registration->attendee_name }}</p>
                        <p class="mt-0.5 truncate text-sm text-slate-600">{{ $registration->attendee_email }}</p>
                    </div>
                    <p class="shrink-0 text-xs text-slate-500">{{ $registration->ticketType?->name ?? '—' }}</p>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    @include('livewire.attendees.partials.status-badge', ['registration' => $registration])
                    @include('livewire.attendees.partials.checkin-badge', ['registration' => $registration])
                </div>
            </a>
        @endforeach

        @if ($attendees->hasPages())
            <div class="pt-2">
                {{ $attendees->links() }}
            </div>
        @endif
    </div>
</div>