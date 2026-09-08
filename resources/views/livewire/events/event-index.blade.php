<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Events</h2>
            <p class="mt-1 text-sm text-slate-600">All events for this organization.</p>
        </div>
        @if ($canCreate)
            <a href="{{ route('app.events.create') }}"
               class="inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Event
            </a>
        @endif
    </div>

    @if (session('status'))
        <div class="rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3">
        <input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="Search events…"
            class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
        />
        <select wire:model.live="statusFilter"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
            <option value="">All active statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Name</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Status</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Mode</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Start Date</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($events as $event)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-900">
                                <a href="{{ route('app.events.setup', $event) }}" class="text-slate-900 hover:text-teal-700 hover:underline">
                                    {{ $event->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
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
                            </td>
                            <td class="px-4 py-3 capitalize text-slate-600">{{ $event->mode ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $event->start_at?->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $event)
                                    <a href="{{ route('app.events.edit', $event) }}"
                                       class="text-teal-600 hover:text-teal-800 font-medium">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                No events found.
                                @if ($canCreate)
                                    <a href="{{ route('app.events.create') }}" class="text-teal-600 hover:underline ml-1">Create one.</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($events->hasPages())
            <div class="border-t border-slate-200 bg-slate-50 px-4 py-3">
                {{ $events->links() }}
            </div>
        @endif
    </div>
</div>
