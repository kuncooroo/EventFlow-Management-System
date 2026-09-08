<div>
    <div class="border-b border-slate-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-slate-900">Registration</h2>
        <p class="mt-1 text-sm text-slate-600">
            Control how attendees register and which default fields are required.
        </p>
    </div>

    @if (session('status'))
        <div class="mx-6 mt-4 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="px-6 py-5">

        {{-- Availability indicator --}}
        <div class="mb-6 flex flex-wrap items-center gap-3 rounded-lg border border-slate-200 bg-slate-50/50 p-4">
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                @if($availability['tone'] === 'slate') bg-slate-100 text-slate-700
                @elseif($availability['tone'] === 'teal') bg-teal-100 text-teal-700
                @elseif($availability['tone'] === 'amber') bg-amber-100 text-amber-700
                @else bg-red-100 text-red-700
                @endif">
                {{ $availability['label'] }}
            </span>
            <p class="text-sm text-slate-600">{{ $availability['note'] }}</p>
        </div>

        <form wire:submit="save" class="space-y-5">

            {{-- Enable --}}
            <div class="flex items-start gap-3">
                <input
                    id="registration-enabled"
                    wire:model="registration_enabled"
                    type="checkbox"
                    value="1"
                    class="mt-0.5 h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                />
                <div>
                    <label for="registration-enabled" class="text-sm font-medium text-slate-700">Enable registration</label>
                    <p class="text-xs text-slate-500">Allow attendees to register for this event once it is published.</p>
                </div>
            </div>

            {{-- Window --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="registration-starts-at" class="block text-sm font-medium text-slate-700">Opens</label>
                    <input
                        id="registration-starts-at"
                        wire:model="registration_starts_at"
                        type="datetime-local"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('registration_starts_at') border-red-400 @enderror"
                    />
                    @error('registration_starts_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="registration-ends-at" class="block text-sm font-medium text-slate-700">Closes</label>
                    <input
                        id="registration-ends-at"
                        wire:model="registration_ends_at"
                        type="datetime-local"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('registration_ends_at') border-red-400 @enderror"
                    />
                    @error('registration_ends_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="-mt-2 text-xs text-slate-500">Leave blank for no limit. Registration only opens once the event is published.</p>

            {{-- Capacity --}}
            <div>
                <label for="registration-capacity" class="block text-sm font-medium text-slate-700">Event Capacity</label>
                <input
                    id="registration-capacity"
                    wire:model="capacity"
                    type="number"
                    min="0"
                    placeholder="Unlimited"
                    class="mt-1 block w-full max-w-xs rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('capacity') border-red-400 @enderror"
                />
                @error('capacity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-slate-500">Leave blank for unlimited capacity.</p>
            </div>

            {{-- Default field requirements --}}
            <div class="space-y-3 rounded-lg border border-slate-200 p-4">
                <p class="text-sm font-medium text-slate-700">Default fields</p>

                <div class="flex items-start gap-3">
                    <input
                        id="require-phone"
                        wire:model="require_phone"
                        type="checkbox"
                        value="1"
                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                    />
                    <label for="require-phone" class="text-sm text-slate-700">Require phone number</label>
                </div>

                <div class="flex items-start gap-3">
                    <input
                        id="require-organization"
                        wire:model="require_organization"
                        type="checkbox"
                        value="1"
                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                    />
                    <label for="require-organization" class="text-sm text-slate-700">Require organization name</label>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <button type="submit"
                        class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    <span wire:loading.remove>Save Settings</span>
                    <span wire:loading>Saving…</span>
                </button>
            </div>
        </form>
    </div>
</div>