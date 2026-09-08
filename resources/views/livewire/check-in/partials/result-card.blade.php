@php
    $isSuccess = $outcome === 'success';
    $isDuplicate = $outcome === 'duplicate';
@endphp
<div class="overflow-hidden rounded-lg border shadow-sm
    @if ($isSuccess) border-teal-200 bg-teal-50
    @elseif ($isDuplicate) border-amber-200 bg-amber-50
    @else border-red-200 bg-red-50 @endif">
    <div class="px-5 py-4">
        <p class="text-lg font-semibold
            @if ($isSuccess) text-teal-900
            @elseif ($isDuplicate) text-amber-900
            @else text-red-900 @endif">
            {{ $isSuccess ? 'Checked In' : ($isDuplicate ? 'Already Checked In' : 'Invalid Ticket') }}
        </p>

        @if ($title)
            <p class="mt-1 text-sm font-medium text-slate-900">{{ $title }}</p>
        @endif

        @if ($detail)
            <p class="mt-0.5 text-sm text-slate-700">{{ $detail }}</p>
        @endif
    </div>
</div>