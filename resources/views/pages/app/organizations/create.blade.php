<x-layouts.app title="Create organization">
    <div class="mx-auto max-w-lg space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Create your organization</h2>
            <p class="mt-1 text-sm text-slate-600">
                Organizations are your workspace boundary for events, members, and reports.
            </p>
        </div>

        @if ($status)
            <x-alert type="success" title="Success">{{ $status }}</x-alert>
        @endif

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('app.organizations.store') }}" class="space-y-4">
                @csrf

                <x-input
                    label="Organization name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    placeholder="Acme Events"
                    required
                    autofocus
                    :error="$errors->first('name')"
                />

                <p class="text-sm text-slate-600">
                    You will become the organization owner and can invite members in a later step.
                </p>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <x-button type="submit">Create organization</x-button>
                    @if (($accessibleOrganizations ?? collect())->isNotEmpty())
                        <a href="{{ route('app.dashboard') }}">
                            <x-button type="button" variant="ghost">Cancel</x-button>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
