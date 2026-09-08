@php
    $isCheckedIn = (bool) ($registration->is_checked_in ?? false);
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
    @if ($isCheckedIn) bg-green-100 text-green-700 @else bg-slate-100 text-slate-700 @endif">
    {{ $isCheckedIn ? 'Checked in' : 'Not checked in' }}
</span>