<div>
    <div class="border-b border-slate-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-slate-900">Reminder</h2>
        <p class="mt-1 text-sm text-slate-600">
            Send a single email reminder to confirmed attendees before the event starts.
        </p>
    </div>

    @if (session('status'))
        <div class="mx-6 mt-4 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="px-6 py-5">
        <form wire:submit="save" class="space-y-5">

            {{-- Enable --}}
            <div class="flex items-start gap-3">
                <input
                    id="reminder-enabled"
                    wire:model="reminder_enabled"
                    type="checkbox"
                    value="1"
                    class="mt-0.5 h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                />
                <div>
                    <label for="reminder-enabled" class="text-sm font-medium text-slate-700">Enable event reminder</label>
                    <p class="text-xs text-slate-500">Attendees are emailed once shortly before the event begins.</p>
                </div>
            </div>

            {{-- Offset --}}
            <div>
                <label for="reminder-hours-before" class="block text-sm font-medium text-slate-700">Remind how long before</label>
                <div class="mt-1 flex max-w-xs items-center gap-2">
                    <input
                        id="reminder-hours-before"
                        wire:model="reminder_hours_before"
                        type="number"
                        min="1"
                        max="720"
                        placeholder="e.g. 24"
                        class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('reminder_hours_before') border-red-400 @enderror"
                    />
                    <span class="text-sm text-slate-500">hours</span>
                </div>
                @error('reminder_hours_before') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @if ($reminder_enabled && $reminder_hours_before !== '' && $event->start_at)
                    <p class="mt-1 text-xs text-slate-500">
                        A single reminder will be sent on {{ $event->reminderDueAt()?->setTimezone($timezone)->format('M j, Y g:i A') }} ({{ $timezone }}).
                    </p>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <button type="submit"
                        class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    <span wire:loading.remove>Save Settings</span>
                    <span wire:loading>Saving…</span>
                </button>
            </div>
        </form>
    </div>
</div>