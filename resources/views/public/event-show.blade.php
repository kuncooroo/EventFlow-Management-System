<x-layouts.public :title="$event->name">
    <article class="space-y-8">
        @if ($banner)
            <img src="{{ $banner->url() }}" alt="{{ $event->name }} banner"
                 class="max-h-72 w-full rounded-xl border border-slate-200 object-cover" />
        @endif

        <header class="space-y-3">
            @if ($cta['state'] === 'cancelled')
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                    This event has been cancelled.
                </div>
            @endif

            <h1 class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                {{ $event->name }}
            </h1>

            <div class="text-sm text-slate-600">
                @if ($startLabel && $endLabel)
                    <p>{{ $startLabel }} — {{ $endLabel }}</p>
                @elseif ($startLabel)
                    <p>{{ $startLabel }}</p>
                @endif
            </div>

            @if ($venue)
                <div class="text-sm text-slate-600">
                    <p class="font-medium text-slate-900">{{ $venue->name }}</p>
                    @if ($venue->address)
                        <p>{{ $venue->address }}</p>
                    @endif
                </div>
            @elseif ($event->mode === 'online')
                <div class="text-sm text-slate-600">
                    <p class="font-medium text-slate-900">Online event</p>
                </div>
            @endif

            @if ($event->organizer_name)
                <p class="text-sm text-slate-600">
                    Organized by <span class="font-medium text-slate-900">{{ $event->organizer_name }}</span>
                </p>
            @endif
        </header>

        <section class="rounded-lg border border-{{ $cta['tone'] === 'teal' ? 'teal' : 'slate' }}-200 bg-{{ $cta['tone'] === 'teal' ? 'teal' : 'slate' }}-50 px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">{{ $cta['label'] }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $cta['note'] }}</p>
                </div>
                @if ($cta['state'] === 'open')
                    <a href="{{ route('public.events.register', $event->public_slug) }}"
                       class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700">
                        {{ $cta['label'] }}
                    </a>
                @endif
            </div>
        </section>

        @if ($event->description)
            <section>
                <h2 class="text-lg font-semibold text-slate-900">About Event</h2>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $event->description }}</p>
            </section>
        @endif

        @if ($agendaItems->isNotEmpty())
            <section>
                <h2 class="text-lg font-semibold text-slate-900">Agenda</h2>
                <ol class="mt-3 space-y-4">
                    @foreach ($agendaItems as $item)
                        <li class="rounded-lg border border-slate-200 bg-white p-4">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <h3 class="text-sm font-semibold text-slate-900">{{ $item->title }}</h3>
                                <p class="text-xs text-slate-500">
                                    {{ $item->start_at ? \Illuminate\Support\Carbon::parse($item->start_at)->setTimezone($timezone)->format('M j, g:i A') : '' }}
                                    @if ($item->end_at)
                                        — {{ \Illuminate\Support\Carbon::parse($item->end_at)->setTimezone($timezone)->format('g:i A') }}
                                    @endif
                                </p>
                            </div>
                            @if ($item->speaker_text)
                                <p class="mt-1 text-xs font-medium text-teal-700">{{ $item->speaker_text }}</p>
                            @endif
                            @if ($item->location)
                                <p class="mt-1 text-xs text-slate-500">{{ $item->location }}</p>
                            @endif
                            @if ($item->description)
                                <p class="mt-2 text-sm text-slate-600">{{ $item->description }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        @if ($ticketTypes->isNotEmpty())
            <section>
                <h2 class="text-lg font-semibold text-slate-900">Ticket Types</h2>
                <ul class="mt-3 divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white">
                    @foreach ($ticketTypes as $ticket)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">{{ $ticket->name }}</h3>
                                @if ($ticket->description)
                                    <p class="mt-0.5 text-sm text-slate-600">{{ $ticket->description }}</p>
                                @endif
                            </div>
                            <p class="text-sm font-medium text-slate-900">
                                @if ($ticket->isFree())
                                    Free
                                @else
                                    {{ $ticket->currency }} {{ number_format((float) $ticket->price_amount, 2) }}
                                @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($event->contact_name || $event->contact_email || $event->contact_phone)
            <section>
                <h2 class="text-lg font-semibold text-slate-900">Organizer Contact</h2>
                <div class="mt-2 space-y-1 text-sm text-slate-600">
                    @if ($event->contact_name)
                        <p>{{ $event->contact_name }}</p>
                    @endif
                    @if ($event->contact_email)
                        <p>{{ $event->contact_email }}</p>
                    @endif
                    @if ($event->contact_phone)
                        <p>{{ $event->contact_phone }}</p>
                    @endif
                </div>
            </section>
        @endif
    </article>
</x-layouts.public>