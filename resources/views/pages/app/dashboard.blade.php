<x-layouts.app title="Dashboard">
    <div class="space-y-6">
        @if (session('status'))
            <x-alert type="success" title="Updated">{{ session('status') }}</x-alert>
        @endif

        <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ $currentOrganization->name ?? 'Dashboard' }}</h2>
            <p class="mt-1 text-sm text-slate-600">
                Welcome back, {{ auth()->user()->name }}. Event metrics will appear here in later tasks.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Events</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">—</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Registrations</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">—</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Check-ins</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">—</p>
            </div>
        </div>

        <x-empty-state
            title="No events yet"
            description="Create your first event once event management is available."
        />
    </div>
</x-layouts.app>
