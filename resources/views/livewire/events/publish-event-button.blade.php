<div>
    @if (session('status'))
        <div class="rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    @if (session('publish_error'))
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('publish_error') }}
        </div>
    @endif

    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Publish Event</h3>
                <p class="mt-1 max-w-md text-sm text-slate-600">
                    Publishing makes this event visible to the public through its unique URL.
                </p>
            </div>

            @if (! $canPublish)
                <span class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-500">
                    Publish permission required
                </span>
            @elseif ($event->status->value !== 'draft')
                <a href="{{ route('app.events.setup', $event) }}"
                   class="inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700">
                    Event is {{ $event->status->label() }}
                </a>
            @elseif ($readinessFailures === [])
                <button wire:click="publish"
                        wire:confirm="Publish this event? It will become publicly accessible."
                        class="inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    <span wire:loading.remove wire:target="publish">Publish Event</span>
                    <span wire:loading wire:target="publish">Publishing…</span>
                </button>
            @else
                <span class="rounded-md border border-amber-300 bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-700"
                      title="Complete the required fields below to publish">
                    Not ready to publish
                </span>
            @endif
        </div>

        @if ($event->status->value === 'draft' && $readinessFailures !== [] && $canPublish)
            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Required to publish</p>
                <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-amber-800">
                    @foreach ($readinessFailures as $failure)
                        <li>{{ $failure }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>