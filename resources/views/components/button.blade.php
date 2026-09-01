@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'disabled' => false,
])

@php
$base = 'inline-flex items-center justify-center gap-2 rounded-md font-medium transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50';

$variants = [
    'primary' => 'bg-teal-700 text-white hover:bg-teal-800 focus-visible:outline-teal-700',
    'secondary' => 'border border-slate-300 bg-white text-slate-800 hover:bg-slate-50 focus-visible:outline-slate-400',
    'danger' => 'bg-red-700 text-white hover:bg-red-800 focus-visible:outline-red-700',
    'ghost' => 'bg-transparent text-slate-700 hover:bg-slate-100 focus-visible:outline-slate-400',
];

$sizes = [
    'sm' => 'px-3 py-1.5 text-xs',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-5 py-2.5 text-sm',
];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->class([$base, $variants[$variant] ?? $variants['primary'], $sizes[$size] ?? $sizes['md']]) }}
    @disabled($disabled)
>
    {{ $slot }}
</button>
