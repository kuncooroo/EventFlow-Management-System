@props([
    'show' => false,
    'title' => null,
])

<div
    x-data="{ open: @js((bool) $show) }"
    x-show="open"
    x-cloak
    {{ $attributes->class(['fixed inset-0 z-50 flex items-center justify-center p-4']) }}
    role="dialog"
    aria-modal="true"
    @keydown.escape.window="open = false"
>
    <div class="absolute inset-0 bg-slate-900/40" @click="open = false"></div>

    <div class="relative w-full max-w-md rounded-lg border border-slate-200 bg-white p-5 shadow-lg">
        <div class="mb-3 flex items-start justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-900">{{ $title ?? 'Dialog' }}</h2>
            <button type="button" class="rounded p-1 text-slate-500 hover:bg-slate-100" @click="open = false" aria-label="Close dialog">
                ×
            </button>
        </div>

        <div class="text-sm text-slate-700">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="mt-4 flex justify-end gap-2">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
