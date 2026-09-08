<div>
    <div class="mb-6">
        <h2 class="text-lg font-medium text-gray-900">Event Assignments</h2>
        <p class="mt-1 text-sm text-gray-600">Assign members to this event.</p>
    </div>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 font-medium text-sm text-red-600">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit="assignMember" class="mb-6 flex gap-4">
        <div>
            <select wire:model="assignee_id" class="border-gray-300 rounded-md shadow-sm">
                <option value="">Select a member...</option>
                @foreach($eligibleMembers as $member)
                    <option value="{{ $member->id }}">{{ $member->user->name }} ({{ $member->role->value }})</option>
                @endforeach
            </select>
            @error('assignee_id') <span class="text-red-500 text-sm block">{{ $message }}</span> @enderror
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">Assign</button>
    </form>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($assignments as $assignment)
                    <tr wire:key="assignment-{{ $assignment->id }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $assignment->membership->user->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $assignment->membership->role->value }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <button wire:click="unassignMember({{ $assignment->membership->id }})" class="text-red-600 hover:text-red-900">Unassign</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-gray-500">No members assigned to this event.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
