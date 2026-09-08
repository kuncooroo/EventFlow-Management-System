<div>
    @if (session('status'))
        <div class="rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    @if (session('cancel_error'))
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('cancel_error') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Administrative Actions</h3>
            <p class="mt-1 max-w-md text-sm text-slate-600">
                Cancelling invalidates the attendee's ticket and prevents check-in.
            </p>
        </div>

        @if ($registration->status->value === 'cancelled')
            <span class="rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700">
                Registration is already cancelled
            </span>
        @elseif (! $canCancel)
            <span class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-500">
                Cancel permission required
            </span>
        @else
            <button
                wire:click="cancel"
                wire:confirm="Cancel this registration? {{ $registration->attendee_name }} will be unable to check in and their ticket is invalidated."
                class="inline-flex items-center rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                <span wire:loading.remove wire:target="cancel">Cancel Registration</span>
                <span wire:loading wire:target="cancel">Cancelling…</span>
            </button>
        @endif
    </div>
</div>