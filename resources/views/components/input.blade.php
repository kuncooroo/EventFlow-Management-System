@props([
    'type' => 'text',
    'id' => null,
    'name' => null,
    'label' => null,
    'error' => null,
])

@php
$inputId = $id ?? $name ?? 'input-'.uniqid();
@endphp

<div {{ $attributes->only('class')->class(['space-y-1']) }}>
    @if ($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-slate-700">
            {{ $label }}
        </label>
    @endif

    <input
        id="{{ $inputId }}"
        type="{{ $type }}"
        @if ($name) name="{{ $name }}" @endif
        {{ $attributes->except('class')->class([
            'block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20',
            'border-red-500 focus:border-red-600 focus:ring-red-600/20' => filled($error),
        ]) }}
    >

    @if ($error)
        <x-form-error :message="$error" />
    @endif
</div>
