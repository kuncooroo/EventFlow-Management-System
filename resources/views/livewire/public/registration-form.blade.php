<div>
    @if ($submitted && $registration)
        <div class="space-y-6">
            <div class="rounded-lg border border-teal-200 bg-teal-50 px-5 py-4">
                <h1 class="text-2xl font-semibold text-slate-900">You're registered!</h1>
                <p class="mt-2 text-sm text-slate-700">
                    Thanks, {{ $registration->attendee_name }}. Your registration for
                    <span class="font-medium text-slate-900">{{ $event->name }}</span> is confirmed.
                </p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-600">Registration ID</p>
                <p class="mt-1 font-mono text-2xl font-semibold tracking-tight text-teal-700">
                    {{ $registration->registration_code }}
                </p>
                @if ($registration->ticket)
                    <a href="{{ route('tickets.public.show', $registration->ticket->ticket_code) }}"
                       class="mt-4 inline-flex rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700">
                        View Ticket
                    </a>
                @endif
            </div>

            <a href="{{ route('public.events.show', $event->public_slug) }}"
               class="inline-block text-sm font-medium text-teal-700 hover:text-teal-800">
                &larr; Back to event
            </a>
        </div>
    @elseif ($cta['state'] !== 'open')
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $event->name }}</h1>
            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-5 py-4">
                <p class="text-sm font-semibold text-slate-900">{{ $cta['label'] }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $cta['note'] }}</p>
            </div>
            <a href="{{ route('public.events.show', $event->public_slug) }}"
               class="mt-4 inline-block text-sm font-medium text-teal-700 hover:text-teal-800">
                &larr; Back to event
            </a>
        </div>
    @else
        <div class="space-y-6">
            <header>
                <a href="{{ route('public.events.show', $event->public_slug) }}"
                   class="text-sm font-medium text-teal-700 hover:text-teal-800">
                    &larr; Back to event
                </a>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Register for {{ $event->name }}</h1>
            </header>

            @error('capacity')
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                    {{ $message }}
                </div>
            @enderror

            @error('event')
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                    {{ $message }}
                </div>
            @enderror

            <form wire:submit="submit" class="space-y-5 rounded-lg border border-slate-200 bg-white p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="attendee-name" class="block text-sm font-medium text-slate-700">Full name *</label>
                        <input
                            id="attendee-name"
                            wire:model="attendee_name"
                            type="text"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('attendee_name') border-red-400 @enderror"
                        />
                        @error('attendee_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="attendee-email" class="block text-sm font-medium text-slate-700">Email *</label>
                        <input
                            id="attendee-email"
                            wire:model="attendee_email"
                            type="email"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('attendee_email') border-red-400 @enderror"
                        />
                        @error('attendee_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="attendee-phone" class="block text-sm font-medium text-slate-700">
                            Phone {{ $event->require_phone ? '*' : '(optional)' }}
                        </label>
                        <input
                            id="attendee-phone"
                            wire:model="attendee_phone"
                            type="text"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('attendee_phone') border-red-400 @enderror"
                        />
                        @error('attendee_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="attendee-organization" class="block text-sm font-medium text-slate-700">
                            Organization {{ $event->require_organization ? '*' : '(optional)' }}
                        </label>
                        <input
                            id="attendee-organization"
                            wire:model="attendee_organization"
                            type="text"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('attendee_organization') border-red-400 @enderror"
                        />
                        @error('attendee_organization') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if ($ticketTypes->isNotEmpty())
                    <div>
                        <label for="ticket-type" class="block text-sm font-medium text-slate-700">Ticket type *</label>
                        <div class="mt-2 space-y-3">
                            @foreach ($ticketTypes as $ticket)
                                <label class="flex cursor-pointer items-start gap-3 rounded-md border border-slate-200 p-3 has-[:checked]:border-teal-500 has-[:checked]:bg-teal-50">
                                    <input
                                        type="radio"
                                        name="ticket_type"
                                        value="{{ $ticket->id }}"
                                        wire:model="ticket_type_id"
                                        class="mt-0.5 h-4 w-4 border-slate-300 text-teal-600 focus:ring-teal-500"
                                    />
                                    <span class="flex flex-1 flex-wrap items-center justify-between gap-2">
                                        <span>
                                            <span class="block text-sm font-medium text-slate-900">{{ $ticket->name }}</span>
                                            @if ($ticket->description)
                                                <span class="block text-xs text-slate-500">{{ $ticket->description }}</span>
                                            @endif
                                        </span>
                                        <span class="text-sm font-semibold text-slate-900">
                                            @if ($ticket->isFree()) Free
                                            @else {{ $ticket->currency }} {{ number_format((float) $ticket->price_amount, 2) }}
                                            @endif
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('ticket_type_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                @foreach ($fields as $field)
                    <div>
                        <label for="field-{{ $field->id }}" class="block text-sm font-medium text-slate-700">
                            {{ $field->label }} {{ $field->is_required ? '*' : '(optional)' }}
                        </label>

                        @if ($field->field_type === \App\Enums\RegistrationFieldType::Textarea)
                            <textarea
                                id="field-{{ $field->id }}"
                                wire:model="answers.{{ $field->id }}"
                                rows="3"
                                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('answers.{{ $field->id }}') border-red-400 @enderror"
                            ></textarea>
                        @elseif ($field->field_type == \App\Enums\RegistrationFieldType::Select)
                            <select
                                id="field-{{ $field->id }}"
                                wire:model="answers.{{ $field->id }}"
                                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('answers.{{ $field->id }}') border-red-400 @enderror"
                            >
                                <option value="">Select…</option>
                                @foreach ($field->options() as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        @elseif ($field->field_type == \App\Enums\RegistrationFieldType::Radio)
                            <div class="mt-2 space-y-2">
                                @foreach ($field->options() as $option)
                                    <label class="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            name="answers-{{ $field->id }}"
                                            value="{{ $option }}"
                                            wire:model="answers.{{ $field->id }}"
                                            class="h-4 w-4 border-slate-300 text-teal-600 focus:ring-teal-500"
                                        />
                                        <span class="text-sm text-slate-700">{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @elseif ($field->field_type == \App\Enums\RegistrationFieldType::Checkbox)
                            <div class="mt-2 space-y-2">
                                @foreach ($field->options() as $option)
                                    <label class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            name="answers-{{ $field->id }}[]"
                                            value="{{ $option }}"
                                            wire:model="answers.{{ $field->id }}"
                                            class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                        />
                                        <span class="text-sm text-slate-700">{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <input
                                id="field-{{ $field->id }}"
                                wire:model="answers.{{ $field->id }}"
                                type="{{ $field->field_type === \App\Enums\RegistrationFieldType::Date ? 'date' : 'text' }}"
                                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('answers.{{ $field->id }}') border-red-400 @enderror"
                            />
                        @endif

                        @error('answers.{{ $field->id }}') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                <div class="flex items-center justify-end border-t border-slate-100 pt-4">
                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="rounded-md bg-teal-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <span wire:loading.remove>Complete Registration</span>
                        <span wire:loading>Submitting…</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
