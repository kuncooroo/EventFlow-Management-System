<div>
    <h3 class="text-sm font-semibold text-slate-900">Organization Logo</h3>
    <p class="mt-1 text-sm text-slate-600">
        Shown in the app header and on public pages. JPEG, PNG, WebP, and GIF up to 2 MB.
    </p>

    @if (session('status'))
        <div class="mt-4 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    @php
        $current = $logo instanceof \App\Models\MediaFile;
    @endphp

    <div class="mt-4 flex flex-wrap items-center gap-4">
        @if ($current)
            <img src="{{ $logo->url() }}" alt="Organization logo"
                 class="h-14 w-14 rounded-lg border border-slate-200 object-contain" />
        @endif

        <label class="inline-flex cursor-pointer items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            <span>{{ $current ? 'Replace Logo' : 'Choose Logo' }}</span>
            <input type="file" wire:model="logo" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" />
        </label>

        <button type="button"
                wire:click="upload"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50"
                class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
            <span wire:loading.remove wire:target="logo, upload">Upload Logo</span>
            <span wire:loading wire:target="logo, upload">Uploading…</span>
        </button>

        @if ($current)
            <button type="button"
                    wire:click="remove"
                    wire:confirm="Remove this logo?"
                    class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                Remove
            </button>
        @endif
    </div>

    @error('logo')
        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>