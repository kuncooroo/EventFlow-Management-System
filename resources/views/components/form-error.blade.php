@props([
    'message' => null,
])

@if ($message || trim($slot) !== '')
    <p {{ $attributes->class(['text-sm text-red-700']) }} role="alert">
        {{ $message ?? $slot }}
    </p>
@endif
