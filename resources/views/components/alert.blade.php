@props([
    'type' => 'info',
    'title' => null,
])

@php
$styles = [
    'info' => 'border-sky-200 bg-sky-50 text-sky-950',
    'success' => 'border-emerald-200 bg-emerald-50 text-emerald-950',
    'warning' => 'border-amber-200 bg-amber-50 text-amber-950',
    'danger' => 'border-red-200 bg-red-50 text-red-950',
];
@endphp

<div {{ $attributes->class(['rounded-md border px-4 py-3 text-sm', $styles[$type] ?? $styles['info']]) }} role="status">
    @if ($title)
        <p class="font-semibold">{{ $title }}</p>
    @endif
    <div @class(['mt-1' => filled($title)])>
        {{ $slot }}
    </div>
</div>
