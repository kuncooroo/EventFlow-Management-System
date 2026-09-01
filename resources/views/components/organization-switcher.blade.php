@props([
    'accessibleOrganizations' => collect(),
    'currentOrganization' => null,
])

@if ($accessibleOrganizations->isEmpty())
    <a
        href="{{ route('app.organizations.create') }}"
        class="rounded-md border border-dashed border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:border-teal-600 hover:text-teal-800"
    >
        Create organization
    </a>
@elseif ($accessibleOrganizations->count() === 1)
    <span class="truncate text-sm font-medium text-slate-700" title="{{ $currentOrganization?->name ?? $accessibleOrganizations->first()->name }}">
        {{ $currentOrganization?->name ?? $accessibleOrganizations->first()->name }}
    </span>
@else
    <form method="POST" action="{{ route('app.organizations.switch') }}" class="flex items-center gap-2">
        @csrf
        <label for="organization-switcher" class="sr-only">Switch organization</label>
        <select
            id="organization-switcher"
            name="organization_id"
            onchange="this.form.submit()"
            class="max-w-[12rem] truncate rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-900 shadow-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20 sm:max-w-xs"
        >
            @foreach ($accessibleOrganizations as $organization)
                <option
                    value="{{ $organization->id }}"
                    @selected($currentOrganization?->id === $organization->id)
                >
                    {{ $organization->name }}
                </option>
            @endforeach
        </select>
    </form>
@endif
