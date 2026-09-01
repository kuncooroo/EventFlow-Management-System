@props([
    'title' => 'Nothing here yet',
    'description' => null,
])

<div {{ $attributes->class(['rounded-lg border border-dashed border-slate-300 bg-white px-6 py-10 text-center']) }}>
    <p class="text-sm font-semibold text-slate-900">{{ $title }}</p>
    @if ($description || trim($slot) !== '')
        <p class="mt-2 text-sm text-slate-600">
            {{ $description ?? $slot }}
        </p>
    @endif
</div>
