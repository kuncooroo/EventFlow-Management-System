<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Members</h2>
            <p class="mt-1 text-sm text-slate-600">
                @if ($canManageMembers)
                    Manage organization members, roles, and pending invitations.
                @else
                    View organization members for {{ $organization->name }}.
                @endif
            </p>
        </div>
        @if ($canManageMembers)
            <x-button type="button" variant="primary" wire:click="openInviteModal">
                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Invite Member
            </x-button>
        @endif
    </div>

    @if ($statusMessage)
        <x-alert type="success" title="Success">{{ $statusMessage }}</x-alert>
    @endif

    @if ($errors->any() && ! $showInviteModal)
        <x-alert type="danger" title="Unable to complete action">
            {{ $errors->first() }}
        </x-alert>
    @endif

    <!-- Active Members Table -->
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
            <h3 class="text-sm font-semibold text-slate-700">Active Members ({{ $memberships->count() }})</h3>
        </div>
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
                                    title="No active members"
                                    description="No members found."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pending Invitations Table -->
    @if ($canManageMembers && $pendingInvitations->isNotEmpty())
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-amber-50/50 px-4 py-3">
                <h3 class="text-sm font-semibold text-amber-900">Pending Invitations ({{ $pendingInvitations->count() }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Email</th>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Role</th>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Invited By</th>
                            <th scope="col" class="px-4 py-3 text-left font-medium text-slate-600">Expires</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($pendingInvitations as $invitation)
                            <tr wire:key="invitation-{{ $invitation->id }}">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ $invitation->email }}
                                </td>
                                <td class="px-4 py-3">
                                    <x-badge variant="warning">{{ $invitation->role->label() }}</x-badge>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $invitation->invitedBy->name }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $invitation->expires_at->diffForHumans() }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <x-button
                                        type="button"
                                        variant="danger"
                                        size="sm"
                                        wire:click="revokeInvite({{ $invitation->id }})"
                                        wire:confirm="Revoke invitation for {{ $invitation->email }}?"
                                    >
                                        Revoke
                                    </x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Invite Member Modal -->
    @if ($showInviteModal)
        <x-modal title="Invite New Member" wire:model="showInviteModal">
            <form wire:submit="sendInvite" class="space-y-4">
                <x-input
                    label="Email Address"
                    name="inviteEmail"
                    type="email"
                    wire:model="inviteEmail"
                    placeholder="colleague@example.com"
                    required
                />

                <div>
                    <label for="inviteRole" class="block text-sm font-medium text-slate-700">Role</label>
                    <select
                        id="inviteRole"
                        wire:model="inviteRole"
                        class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20"
                    >
                        @foreach ($assignableRoles as $role)
                            <option value="{{ $role->value }}">{{ $role->label() }}</option>
                        @endforeach
                    </select>
                    @error('inviteRole')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                    <x-button type="button" variant="secondary" wire:click="closeInviteModal">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="primary">
                        Send Invitation
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
