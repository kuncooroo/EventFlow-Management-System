<div>
    <div class="border-b border-slate-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-slate-900">Custom Fields</h2>
        <p class="mt-1 text-sm text-slate-600">
            Ask attendees for extra information during registration. Only active fields appear on the public form.
        </p>
    </div>

    @if (session('status'))
        <div class="mx-6 mt-4 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mx-6 mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="px-6 py-5">

        {{-- Add / edit form --}}
        @if ($showForm)
            <form wire:submit="save" class="mb-6 space-y-5 rounded-lg border border-slate-200 bg-slate-50/50 p-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">
                        {{ $editingId ? 'Edit Field' : 'Add Field' }}
                    </h3>
                    <button type="button" wire:click="cancel" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                </div>

                <div>
                    <label for="field-label" class="block text-sm font-medium text-slate-700">
                        Label <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="field-label"
                        wire:model="label"
                        type="text"
                        maxlength="200"
                        placeholder="e.g. Institution"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('label') border-red-400 @enderror"
                    />
                    @error('label') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="field-type" class="block text-sm font-medium text-slate-700">
                            Field Type <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="field-type"
                            wire:model="field_type"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('field_type') border-red-400 @enderror"
                        >
                            <option value="">— Select type —</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        @error('field_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="flex items-start gap-3">
                            <input
                                wire:model="is_required"
                                type="checkbox"
                                value="1"
                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                            />
                            <span class="text-sm font-medium text-slate-700">Required</span>
                        </label>
                    </div>
                </div>

                {{-- Options editor (choice fields only) --}}
                <div
                    x-data="{ fieldType: @entangle('field_type') }"
                    x-cloak
                    x-show="fieldType === 'select' || fieldType === 'radio' || fieldType === 'checkbox'"
                    class="space-y-2"
                >
                    <label for="field-options" class="block text-sm font-medium text-slate-700">
                        Options <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="field-options"
                        wire:model="options_text"
                        rows="4"
                        placeholder="One option per line"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('options_text') border-red-400 @enderror"
                    ></textarea>
                    @error('options_text') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    <p class="text-xs text-slate-500">Enter one option per line. These choices appear on the registration form.</p>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="submit"
                            class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <span wire:loading.remove>{{ $editingId ? 'Save Changes' : 'Add Field' }}</span>
                        <span wire:loading>Saving…</span>
                    </button>
                </div>
            </form>
        @endif

        {{-- Empty state --}}
        @if ($fields->isEmpty() && ! $showForm)
            <div class="flex flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 px-6 py-10 text-center">
                <h3 class="text-sm font-semibold text-slate-900">No custom fields yet</h3>
                <p class="mt-1 max-w-sm text-sm text-slate-600">Add questions to collect extra information from attendees.</p>
                <button wire:click="beginCreate"
                        class="mt-4 rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    Add Field
                </button>
            </div>
        @endif

        {{-- Fields list --}}
        @if ($fields->isNotEmpty())
            @if (! $showForm)
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-xs text-slate-500">Order controls the registration form layout.</p>
                    <button wire:click="beginCreate"
                            class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        + Add Field
                    </button>
                </div>
            @endif

            <ul class="divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200">
                @foreach ($fields as $field)
                    <li wire:key="field-{{ $field->id }}" class="flex flex-wrap items-center gap-3 px-5 py-4">
                        <div class="flex items-center gap-1">
                            @if (! $loop->first)
                                <button wire:click="moveUp({{ $field->id }})" title="Move up" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">&uarr;</button>
                            @endif
                            @if (! $loop->last)
                                <button wire:click="moveDown({{ $field->id }})" title="Move down" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">&darr;</button>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $field->label }}
                                @if ($field->is_required)
                                    <span class="text-red-500">*</span>
                                @endif
                            </p>
                            <p class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                <span>{{ $field->field_type?->label() }}</span>
                                @if ($field->field_type?->requiresOptions())
                                    <span>({{ count($field->options()) }} options)</span>
                                @endif
                                @if ($field->is_active)
                                    <span class="inline-flex items-center rounded-full bg-teal-100 px-2 py-0.5 font-medium text-teal-700">Active</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-600">Inactive</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-1 text-sm">
                            <button wire:click="beginEdit({{ $field->id }})" class="rounded px-2 py-1 text-slate-600 hover:bg-slate-100">Edit</button>
                            @if ($field->is_active)
                                <button wire:click="setActive({{ $field->id }}, false)"
                                        class="rounded px-2 py-1 text-amber-700 hover:bg-amber-50">Deactivate</button>
                            @else
                                <button wire:click="setActive({{ $field->id }}, true)"
                                        class="rounded px-2 py-1 text-teal-700 hover:bg-teal-50">Activate</button>
                            @endif
                            <button wire:click="delete({{ $field->id }})"
                                    wire:confirm="Delete this field? Deactivation preserves historical responses; deletion does not."
                                    class="rounded px-2 py-1 text-red-600 hover:bg-red-50">Delete</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>