<div>
    @if (session('status'))
        <div class="rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    @if (session('checkin_error'))
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('checkin_error') }}
        </div>
    @endif

    @if ($registration->status->value === 'confirmed')
        @if (! $registration->is_checked_in)
            @if ($canCheckIn)
                <button
                    wire:click="checkIn"
                    class="mt-2 inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                    <span wire:loading.remove wire:target="checkIn">Check In</span>
                    <span wire:loading wire:target="checkIn">Checking in…</span>
                </button>
            @else
                <p class="mt-2 text-xs text-slate-500">Check-in permission required.</p>
            @endif
        @else
            <p class="mt-2 text-sm text-slate-700">This attendee has already checked in.</p>
        @endif
    @else
        <p class="mt-2 text-sm text-red-700">Cancelled registrations cannot be checked in.</p>
    @endif
</div>