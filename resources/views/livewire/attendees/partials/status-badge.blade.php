@php
    $color = match ($registration->status) {
        \App\Enums\RegistrationStatus::Confirmed => 'teal',
        \App\Enums\RegistrationStatus::Cancelled => 'red',
        default => 'slate',
    };
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
    @if($color === 'slate') bg-slate-100 text-slate-700
    @elseif($color === 'teal') bg-teal-100 text-teal-700
    @else bg-red-100 text-red-700
    @endif">
    {{ $registration->status->label() }}
</span>