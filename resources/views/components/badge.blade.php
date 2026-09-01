@props([
    'variant' => 'neutral',
])

@php
$variants = [
    'neutral' => 'bg-slate-100 text-slate-700',
    'success' => 'bg-emerald-50 text-emerald-800',
    'warning' => 'bg-amber-50 text-amber-900',
    'danger' => 'bg-red-50 text-red-800',
    'info' => 'bg-sky-50 text-sky-800',
];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium', $variants[$variant] ?? $variants['neutral']]) }}>
    {{ $slot }}
</span>
