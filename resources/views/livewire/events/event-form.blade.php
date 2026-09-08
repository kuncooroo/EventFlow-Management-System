<div class="mx-auto max-w-3xl space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
            <p class="mt-1 text-sm text-slate-600">
                @if($isEditing)
                    Update the details for this draft event.
                @else
                    Create a new Draft event in your organization.
                @endif
            </p>
        </div>
        <a href="{{ route('app.events.index') }}"
           class="text-sm text-slate-500 hover:text-slate-700">&larr; Back to Events</a>
    </div>

    @if ($errors->any())
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">Please fix the following errors:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form wire:submit="save" class="space-y-5 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">

        {{-- Name --}}
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">
                Event Name <span class="text-red-500">*</span>
            </label>
            <input
                id="name"
                wire:model="name"
                type="text"
                maxlength="200"
                placeholder="e.g. Annual Tech Conference 2026"
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('name') border-red-400 @enderror"
            />
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Description --}}
        <div>
            <label for="description" class="block text-sm font-medium text-slate-700">Description</label>
            <textarea
                id="description"
                wire:model="description"
                rows="4"
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
            ></textarea>
        </div>

        {{-- Organizer Name --}}
        <div>
            <label for="organizer_name" class="block text-sm font-medium text-slate-700">Organizer Name</label>
            <input
                id="organizer_name"
                wire:model="organizer_name"
                type="text"
                maxlength="150"
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
            />
        </div>

        {{-- Mode --}}
        <div>
            <label for="mode" class="block text-sm font-medium text-slate-700">Event Mode</label>
            <select
                id="mode"
                wire:model="mode"
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
            >
                <option value="">— Select mode —</option>
                <option value="online">Online</option>
                <option value="offline">Offline</option>
                <option value="hybrid">Hybrid</option>
            </select>
            @error('mode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Dates --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="start_at" class="block text-sm font-medium text-slate-700">Start Date &amp; Time</label>
                <input
                    id="start_at"
                    wire:model="start_at"
                    type="datetime-local"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('start_at') border-red-400 @enderror"
                />
                @error('start_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="end_at" class="block text-sm font-medium text-slate-700">End Date &amp; Time</label>
                <input
                    id="end_at"
                    wire:model="end_at"
                    type="datetime-local"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('end_at') border-red-400 @enderror"
                />
                @error('end_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Capacity --}}
        <div>
            <label for="capacity" class="block text-sm font-medium text-slate-700">Capacity</label>
            <input
                id="capacity"
                wire:model="capacity"
                type="number"
                min="0"
                placeholder="Leave blank for unlimited"
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('capacity') border-red-400 @enderror"
            />
            @error('capacity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-slate-500">Leave blank for unlimited capacity.</p>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
            <a href="{{ route('app.events.index') }}"
               class="rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</a>
            <button type="submit"
                    class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                <span wire:loading.remove>{{ $isEditing ? 'Save Changes' : 'Create Event' }}</span>
                <span wire:loading>Saving…</span>
            </button>
        </div>
    </form>
</div>
