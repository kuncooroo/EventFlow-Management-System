<div>
    @if (session('status'))
        <div class="rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    @if (session('lifecycle_error'))
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('lifecycle_error') }}
        </div>
    @endif

    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Event Lifecycle</h3>
                <p class="mt-1 max-w-md text-sm text-slate-600">
                    The event is currently <span class="font-medium text-slate-800">{{ $event->status->label() }}</span>.
                </p>
            </div>
        </div>

        @php $status = $event->status->value; @endphp

        <div class="mt-4 flex flex-wrap items-center gap-3">
            @if ($status === 'draft' && $canCancel)
                <button
                    wire:click="cancelEvent"
                    wire:confirm="Cancel this event before publication? Attendance history and configuration are preserved."
                    class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    <span wire:loading.remove wire:target="cancelEvent">Cancel Event</span>
                    <span wire:loading wire:target="cancelEvent">Cancelling…</span>
                </button>
            @endif

            @if ($status === 'published' && $canStart)
                <button
                    wire:click="startEvent"
                    wire:confirm="Start this event? Its status will change to Ongoing."
                    class="inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                    <span wire:loading.remove wire:target="startEvent">Start Event</span>
                    <span wire:loading wire:target="startEvent">Starting…</span>
                </button>
            @endif

            @if ($status === 'ongoing' && $canComplete)
                <button
                    wire:click="completeEvent"
                    wire:confirm="Complete this event? It will remain available for reporting."
                    class="inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                    <span wire:loading.remove wire:target="completeEvent">Complete Event</span>
                    <span wire:loading wire:target="completeEvent">Completing…</span>
                </button>
            @endif

            @if (in_array($status, ['published', 'ongoing'], true) && $canCancel)
                <button
                    wire:click="cancelEvent"
                    wire:confirm="Cancel this event? New registrations will be blocked and attendance history will be preserved."
                    class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    <span wire:loading.remove wire:target="cancelEvent">Cancel Event</span>
                    <span wire:loading wire:target="cancelEvent">Cancelling…</span>
                </button>
            @endif

            @if (in_array($status, ['completed', 'cancelled'], true) && $canArchive)
                <button
                    wire:click="archiveEvent"
                    wire:confirm="Archive this event? Archiving is not destructive — it stays available in historical views."
                    class="inline-flex items-center rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    <span wire:loading.remove wire:target="archiveEvent">Archive Event</span>
                    <span wire:loading wire:target="archiveEvent">Archiving…</span>
                </button>
            @endif

            @if ($status === 'archived')
                <span class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-500">
                    Archived events cannot be restored in the MVP.
                </span>
            @endif

            @if ($status !== 'archived' && ! $canStart && ! $canComplete && ! $canCancel && ! $canArchive)
                <span class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-500">
                    Lifecycle permission required
                </span>
            @endif
        </div>
    </div>
</div>