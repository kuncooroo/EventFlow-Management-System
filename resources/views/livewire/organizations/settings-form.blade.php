<div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('app.dashboard') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Back to Dashboard</a>
            <h2 class="mt-2 text-lg font-semibold text-slate-900">Organization Settings</h2>
            <p class="mt-1 text-sm text-slate-600">Configure workspace defaults and branding.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- General --}}
    <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-900">General</h3>
            <p class="mt-1 text-sm text-slate-600">Date and time presentation uses the configured timezone.</p>
        </div>

        <div class="px-6 py-5">
            <form wire:submit="save" class="space-y-5">
                <div>
                    <label for="org-name" class="block text-sm font-medium text-slate-700">Organization name</label>
                    <input
                        id="org-name"
                        wire:model="name"
                        type="text"
                        class="mt-1 block w-full max-w-lg rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('name') border-red-400 @enderror"
                    />
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="org-timezone" class="block text-sm font-medium text-slate-700">Timezone</label>
                        <select
                            id="org-timezone"
                            wire:model="timezone"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('timezone') border-red-400 @enderror"
                        >
                            @foreach ($timezones as $timezone)
                                <option value="{{ $timezone }}">{{ $timezone }}</option>
                            @endforeach
                        </select>
                        @error('timezone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="org-locale" class="block text-sm font-medium text-slate-700">Locale</label>
                        <select
                            id="org-locale"
                            wire:model="locale"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('locale') border-red-400 @enderror"
                        >
                            @foreach ($supportedLocales as $locale)
                                <option value="{{ $locale }}">{{ $locale }}</option>
                            @endforeach
                        </select>
                        @error('locale') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="org-currency" class="block text-sm font-medium text-slate-700">Default currency</label>
                    <input
                        id="org-currency"
                        wire:model="default_currency"
                        type="text"
                        maxlength="3"
                        placeholder="USD"
                        class="mt-1 block w-full max-w-xs rounded-md border border-slate-300 px-3 py-2 text-sm uppercase shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('default_currency') border-red-400 @enderror"
                    />
                    @error('default_currency') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-slate-500">Three-letter ISO-4217 code. Used as the default when creating ticket types.</p>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                    <button type="submit"
                            class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <span wire:loading.remove>Save Settings</span>
                        <span wire:loading>Saving…</span>
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{-- Branding --}}
    <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="px-6 py-5">
            <livewire:organizations.logo-upload :organization="$organization" :key="'logo-'.$organization->id" />
        </div>
    </section>
</div>