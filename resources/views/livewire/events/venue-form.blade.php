<div>
    {{-- Header --}}
    <div class="border-b border-slate-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-slate-900">Venue</h2>
        <p class="mt-1 text-sm text-slate-600">
            Set how your attendees experience this event — online, in-person, or both — and which details are shared publicly.
        </p>
    </div>

    @if (session('status'))
        <div class="mx-6 mt-4 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-5 px-6 py-5">

        {{-- Mode --}}
        <div
            x-data="{ mode: @entangle('mode') }"
            class="space-y-5"
        >
            <div>
                <label for="venue-mode" class="block text-sm font-medium text-slate-700">
                    Event Mode <span class="text-red-500">*</span>
                </label>
                <select
                    id="venue-mode"
                    wire:model="mode"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('mode') border-red-400 @enderror"
                >
                    <option value="">— Select mode —</option>
                    @foreach ($modes as $option)
                        <option value="{{ $option->value }}">
                            {{ $option->label() }}
                        </option>
                    @endforeach
                </select>
                @error('mode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Physical venue details (hidden for Online) --}}
            <div x-show="mode !== 'online'" x-cloak class="space-y-5">
                <div>
                    <label for="venue-name" class="block text-sm font-medium text-slate-700">
                        Venue Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="venue-name"
                        wire:model="name"
                        type="text"
                        maxlength="200"
                        placeholder="e.g. Jakarta Convention Center"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('name') border-red-400 @enderror"
                    />
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="venue-address" class="block text-sm font-medium text-slate-700">Address</label>
                    <textarea
                        id="venue-address"
                        wire:model="address"
                        rows="3"
                        placeholder="Street, city, country"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                    ></textarea>
                </div>
            </div>
        </div>

        <div>
            <label for="venue-notes" class="block text-sm font-medium text-slate-700">Internal Notes</label>
            <textarea
                id="venue-notes"
                wire:model="notes"
                rows="3"
                placeholder="Only visible to your team — never shown on the public event page."
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
            ></textarea>
            <p class="mt-1 text-xs text-slate-500">Notes are internal and will never appear on the public page.</p>
        </div>

        {{-- Public visibility --}}
        <div class="flex items-start gap-3">
            <input
                id="venue-is-public"
                wire:model="is_public"
                type="checkbox"
                value="1"
                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
            />
            <div>
                <label for="venue-is-public" class="text-sm font-medium text-slate-700">Publicly visible</label>
                <p class="text-xs text-slate-500">Show this venue and address on the public event page.</p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
            <button type="submit"
                    class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                <span wire:loading.remove>Save Venue</span>
                <span wire:loading>Saving…</span>
            </button>
        </div>
    </form>
</div>