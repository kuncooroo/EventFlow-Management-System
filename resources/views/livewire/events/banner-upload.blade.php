<div>
    <div class="border-b border-slate-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-slate-900">Event Banner</h2>
        <p class="mt-1 text-sm text-slate-600">
            A banner shown on the public event page. JPEG, PNG, WebP, and GIF up to 5 MB.
        </p>
    </div>

    @if (session('status'))
        <div class="mx-6 mt-4 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="px-6 py-5">
        @if ($banner instanceof \App\Models\MediaFile)
            <img src="{{ $banner->url() }}" alt="{{ $event->name }} banner"
                 class="mb-4 max-h-48 w-full rounded-lg border border-slate-200 object-cover" />
        @endif

        @php
        $current = $banner instanceof \App\Models\MediaFile;
        @endphp

        <div class="flex flex-wrap items-center gap-4">
            <label class="inline-flex cursor-pointer items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                <span>{{ $current ? 'Replace Banner' : 'Choose Banner' }}</span>
                <input type="file" wire:model="banner" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" />
            </label>

            <button type="button"
                    wire:click="upload"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50"
                    class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                <span wire:loading.remove wire:target="banner, upload">Upload Banner</span>
                <span wire:loading wire:target="banner, upload">Uploading…</span>
            </button>

            @if ($current)
                <button type="button"
                        wire:click="remove"
                        wire:confirm="Remove this banner?"
                        class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                    Remove
                </button>
            @endif
        </div>

        @error('banner')
            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>