<div class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">{{ $organization->name }}</h2>
        <p class="mt-1 text-sm text-slate-600">Welcome back, {{ auth()->user()->name }}.</p>
    </div>

    {{-- KPI cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Active Events</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $activeEvents }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Upcoming</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $upcomingEvents }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Registrations</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $totalRegistrations }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $confirmedRegistrations }} confirmed</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Checked In</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $checkedIn }}</p>
        </div>
    </div>

    {{-- Status overview --}}
    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <h3 class="text-sm font-semibold text-slate-900">Events by status</h3>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($statusCounts as $statusValue => $count)
                @php
                    $status = \App\Enums\EventStatus::from($statusValue);
                    $color = $status->badgeColor();
                @endphp
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                        @if($color === 'slate') bg-slate-100 text-slate-700
                        @elseif($color === 'teal') bg-teal-100 text-teal-700
                        @elseif($color === 'blue') bg-blue-100 text-blue-700
                        @elseif($color === 'green') bg-green-100 text-green-700
                        @elseif($color === 'red') bg-red-100 text-red-700
                        @else bg-amber-100 text-amber-700
                        @endif">
                        {{ $status->label() }}
                    </span>
                    <span class="text-slate-900">{{ $count }}</span>
                </span>
            @endforeach
        </div>
    </div>

    {{-- Upcoming events --}}
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Upcoming Events</h3>
        </div>
        @if ($upcoming->isEmpty())
            <div class="px-5 py-4">
                <x-empty-state title="No upcoming events" description="Published events will appear here." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Event</th>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Starts</th>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($upcoming as $event)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    <a href="{{ route('app.events.setup', $event) }}" class="hover:text-teal-700 hover:underline">
                                        {{ $event->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $event->start_at?->format('M j, Y g:i A') ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $event->status->label() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Recent events --}}
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Recent Events</h3>
        </div>
        @if ($recent->isEmpty())
            <div class="px-5 py-4">
                <x-empty-state
                    title="No events yet"
                    description="Create your first event to get started."
                />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Event</th>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Created</th>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($recent as $event)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    <a href="{{ route('app.events.setup', $event) }}" class="hover:text-teal-700 hover:underline">
                                        {{ $event->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $event->created_at->format('M j, Y') }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $event->status->label() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>