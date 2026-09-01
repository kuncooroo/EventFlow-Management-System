<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Members</h2>
            <p class="mt-1 text-sm text-slate-600">
                @if ($canManageMembers)
                    Manage organization members and their roles.
                @else
                    View organization members for {{ $organization->name }}.
                @endif
            </p>
        </div>
    </div>

    @if ($statusMessage)
        <x-alert type="success" title="Updated">{{ $statusMessage }}</x-alert>
    @endif

    @if ($errors->any())
        <x-alert type="danger" title="Unable to complete action">
            {{ $errors->first() }}
        </x-alert>
    @endif

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Name</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Email</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Role</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Joined</th>
                        @if ($canManageMembers)
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($memberships as $membership)
                        <tr wire:key="member-{{ $membership->id }}">
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ $membership->user->name }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $membership->user->email }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($canManageMembers)
                                    <select
                                        class="rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-900 shadow-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20"
                                        wire:change="changeRole({{ $membership->id }}, $event.target.value)"
                                    >
                                        @foreach ($assignableRoles as $role)
                                            <option value="{{ $role->value }}" @selected($membership->role === $role)>
                                                {{ $role->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <x-badge variant="info">{{ $membership->role->label() }}</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $membership->joined_at?->format('M j, Y') ?? '—' }}
                            </td>
                            @if ($canManageMembers)
                                <td class="px-4 py-3 text-right">
                                    <x-button
                                        type="button"
                                        variant="danger"
                                        size="sm"
                                        wire:click="removeMember({{ $membership->id }})"
                                        wire:confirm="Remove {{ $membership->user->name }} from this organization?"
                                    >
                                        Remove
                                    </x-button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManageMembers ? 5 : 4 }}" class="px-4 py-8">
                                <x-empty-state
                                    title="No members yet"
                                    description="Invitations arrive in a later task."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
