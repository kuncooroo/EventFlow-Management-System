<div>
    <div class="border-b border-slate-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-slate-900">Ticket Types</h2>
        <p class="mt-1 text-sm text-slate-600">
            Define how attendees register: free or informational-price types, capacity, and availability.
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
                        {{ $editingId ? 'Edit Ticket Type' : 'Add Ticket Type' }}
                    </h3>
                    <button type="button" wire:click="cancel" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="ticket-name" class="block text-sm font-medium text-slate-700">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="ticket-name"
                            wire:model="name"
                            type="text"
                            maxlength="150"
                            placeholder="e.g. General Admission"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('name') border-red-400 @enderror"
                        />
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="ticket-price" class="block text-sm font-medium text-slate-700">Price</label>
                            <input
                                id="ticket-price"
                                wire:model="price_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('price_amount') border-red-400 @enderror"
                            />
                            @error('price_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="ticket-currency" class="block text-sm font-medium text-slate-700">Currency</label>
                            <input
                                id="ticket-currency"
                                wire:model="currency"
                                type="text"
                                maxlength="3"
                                placeholder="USD"
                                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('currency') border-red-400 @enderror"
                            />
                            @error('currency') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label for="ticket-description" class="block text-sm font-medium text-slate-700">Description</label>
                    <textarea
                        id="ticket-description"
                        wire:model="description"
                        rows="2"
                        placeholder="What does this ticket include?"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                    ></textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label for="ticket-capacity" class="block text-sm font-medium text-slate-700">Capacity</label>
                        <input
                            id="ticket-capacity"
                            wire:model="capacity"
                            type="number"
                            min="0"
                            placeholder="Unlimited"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('capacity') border-red-400 @enderror"
                        />
                        @error('capacity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-slate-500">Leave blank for unlimited; 0 blocks all registrations.</p>
                    </div>
                    <div>
                        <label for="ticket-from" class="block text-sm font-medium text-slate-700">Available From</label>
                        <input
                            id="ticket-from"
                            wire:model="available_from"
                            type="datetime-local"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('available_from') border-red-400 @enderror"
                        />
                        @error('available_from') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ticket-until" class="block text-sm font-medium text-slate-700">Available Until</label>
                        <input
                            id="ticket-until"
                            wire:model="available_until"
                            type="datetime-local"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('available_until') border-red-400 @enderror"
                        />
                        @error('available_until') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                    Prices are informational only. No online payment is processed in this version.
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="submit"
                            class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <span wire:loading.remove>{{ $editingId ? 'Save Changes' : 'Add Ticket Type' }}</span>
                        <span wire:loading>Saving…</span>
                    </button>
                </div>
            </form>
        @endif

        {{-- Empty state --}}
        @if ($ticketTypes->isEmpty() && ! $showForm)
            <div class="flex flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 px-6 py-10 text-center">
                <h3 class="text-sm font-semibold text-slate-900">No ticket types yet</h3>
                <p class="mt-1 max-w-sm text-sm text-slate-600">Add ticket categories attendees can register under.</p>
                <button wire:click="beginCreate"
                        class="mt-4 rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    Add Ticket Type
                </button>
            </div>
        @endif

        {{-- Ticket types list --}}
        @if ($ticketTypes->isNotEmpty())
            @if (! $showForm)
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-xs text-slate-500">Order controls the registration form layout.</p>
                    <button wire:click="beginCreate"
                            class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        + Add Ticket Type
                    </button>
                </div>
            @endif

            <ul class="divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200">
                @foreach ($ticketTypes as $ticketType)
                    <li wire:key="ticket-{{ $ticketType->id }}" class="flex flex-wrap items-center gap-3 px-5 py-4">
                        <div class="flex items-center gap-1">
                            @if (! $loop->first)
                                <button wire:click="moveUp({{ $ticketType->id }})" title="Move up" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">&uarr;</button>
                            @endif
                            @if (! $loop->last)
                                <button wire:click="moveDown({{ $ticketType->id }})" title="Move down" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">&darr;</button>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $ticketType->name }}
                                @if ($ticketType->is_active)
                                    <span class="ml-1 inline-flex items-center rounded-full bg-teal-100 px-2 py-0.5 text-xs font-medium text-teal-700">Active</span>
                                @else
                                    <span class="ml-1 inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Inactive</span>
                                @endif
                            </p>
                            <p class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                @if ($ticketType->isFree())
                                    <span class="font-medium text-teal-700">Free</span>
                                @else
                                    <span class="font-medium text-slate-700">{{ $ticketType->price_amount }} {{ $ticketType->currency }}</span>
                                @endif
                                <span>|</span>
                                <span>{{ $ticketType->capacity === null ? 'Unlimited' : $ticketType->capacity.' max' }}</span>
                                @if ($ticketType->available_from || $ticketType->available_until)
                                    <span>|</span>
                                    <span>{{ $ticketType->available_from?->format('M j, Y g:i A') ?? '—' }} – {{ $ticketType->available_until?->format('M j, Y g:i A') ?? '—' }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-1 text-sm">
                            <button wire:click="beginEdit({{ $ticketType->id }})" class="rounded px-2 py-1 text-slate-600 hover:bg-slate-100">Edit</button>
                            @if ($ticketType->is_active)
                                <button wire:click="setActive({{ $ticketType->id }}, false)"
                                        class="rounded px-2 py-1 text-amber-700 hover:bg-amber-50">Deactivate</button>
                            @else
                                <button wire:click="setActive({{ $ticketType->id }}, true)"
                                        class="rounded px-2 py-1 text-teal-700 hover:bg-teal-50">Activate</button>
                            @endif
                            <button wire:click="delete({{ $ticketType->id }})"
                                    wire:confirm="Delete this ticket type? This cannot be undone."
                                    class="rounded px-2 py-1 text-red-600 hover:bg-red-50">Delete</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>