<div>
    <div class="border-b border-slate-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-slate-900">Agenda</h2>
        <p class="mt-1 text-sm text-slate-600">
            Build your event schedule. Sessions display chronologically by default.
        </p>
    </div>

    @if (session('status'))
        <div class="mx-6 mt-4 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mx-6 mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="px-6 py-5">
        {{-- Add / edit form --}}
        @if ($showForm)
            <form wire:submit="save" class="mb-6 space-y-5 rounded-lg border border-slate-200 bg-slate-50/50 p-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">
                        {{ $editingId ? 'Edit Session' : 'Add Session' }}
                    </h3>
                    <button type="button" wire:click="cancel" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                </div>

                <div>
                    <label for="agenda-title" class="block text-sm font-medium text-slate-700">
                        Title <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="agenda-title"
                        wire:model="title"
                        type="text"
                        maxlength="200"
                        placeholder="e.g. Opening Keynote"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('title') border-red-400 @enderror"
                    />
                    @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="agenda-start" class="block text-sm font-medium text-slate-700">
                            Start <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="agenda-start"
                            wire:model="start_at"
                            type="datetime-local"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('start_at') border-red-400 @enderror"
                        />
                        @error('start_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="agenda-end" class="block text-sm font-medium text-slate-700">End</label>
                        <input
                            id="agenda-end"
                            wire:model="end_at"
                            type="datetime-local"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('end_at') border-red-400 @enderror"
                        />
                        @error('end_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="agenda-location" class="block text-sm font-medium text-slate-700">Location</label>
                        <input
                            id="agenda-location"
                            wire:model="location"
                            type="text"
                            maxlength="200"
                            placeholder="e.g. Main Stage"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        />
                    </div>
                    <div>
                        <label for="agenda-speaker" class="block text-sm font-medium text-slate-700">Speaker</label>
                        <input
                            id="agenda-speaker"
                            wire:model="speaker_text"
                            type="text"
                            maxlength="255"
                            placeholder="e.g. Jane Doe"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        />
                    </div>
                </div>

                <div>
                    <label for="agenda-description" class="block text-sm font-medium text-slate-700">Description</label>
                    <textarea
                        id="agenda-description"
                        wire:model="description"
                        rows="3"
                        placeholder="What happens in this session?"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="submit"
                            class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <span wire:loading.remove>{{ $editingId ? 'Save Changes' : 'Add Session' }}</span>
                        <span wire:loading>Saving…</span>
                    </button>
                </div>
            </form>
        @endif

        {{-- Empty state --}}
        @if ($items->isEmpty() && ! $showForm)
            <div class="flex flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 px-6 py-10 text-center">
                <h3 class="text-sm font-semibold text-slate-900">No agenda items yet</h3>
                <p class="mt-1 max-w-sm text-sm text-slate-600">Add sessions or activities to build your event schedule.</p>
                <button wire:click="beginCreate"
                        class="mt-4 rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    Add Agenda Item
                </button>
            </div>
        @endif

        {{-- Agenda list --}}
        @if ($items->isNotEmpty())
            @if (! $showForm)
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-xs text-slate-500">Sorted by start time, then manual order.</p>
                    <button wire:click="beginCreate"
                            class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        + Add Session
                    </button>
                </div>
            @endif

            <ul class="divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200">
                @foreach ($items as $item)
                    <li wire:key="agenda-item-{{ $item->id }}" class="flex flex-wrap items-start gap-4 px-5 py-4">
                        <div class="min-w-[130px] text-sm">
                            <p class="font-medium text-slate-900">{{ $item->start_at?->format('g:i A') }}</p>
                            @if ($item->end_at)
                                <p class="text-xs text-slate-500">{{ $item->end_at?->format('g:i A') }}</p>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">{{ $item->title }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ collect([$item->location, $item->speaker_text])->filter()->implode(' · ') }}
                            </p>
                            @if ($item->description)
                                <p class="mt-1 text-sm text-slate-600">{{ $item->description }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-1">
                            @if (! $loop->first)
                                <button wire:click="moveUp({{ $item->id }})" title="Move up" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                                    &uarr;
                                </button>
                            @endif
                            @if (! $loop->last)
                                <button wire:click="moveDown({{ $item->id }})" title="Move down" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                                    &darr;
                                </button>
                            @endif
                            <button wire:click="beginEdit({{ $item->id }})" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">Edit</button>
                            <button wire:click="delete({{ $item->id }})" wire:confirm="Delete this agenda item?" class="rounded p-1 text-red-500 hover:bg-red-50 hover:text-red-700">Delete</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>