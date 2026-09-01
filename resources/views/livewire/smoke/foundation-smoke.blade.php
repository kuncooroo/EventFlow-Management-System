<div class="space-y-4" data-testid="foundation-smoke">
    <div class="flex flex-wrap items-center gap-3">
        <x-badge>Livewire</x-badge>
        <x-badge variant="success">Tailwind</x-badge>
        <x-badge variant="info">Alpine</x-badge>
    </div>

    <x-alert type="info" title="Foundation smoke check">
        This interactive component confirms Livewire rendering and Alpine toggles on the public shell.
    </x-alert>

    <div
        x-data="{ open: false }"
        class="rounded-lg border border-slate-200 bg-white p-4"
    >
        <div class="flex flex-wrap items-center gap-3">
            <x-button type="button" @click="open = !open" variant="secondary">
                <span x-text="open ? 'Hide Alpine panel' : 'Show Alpine panel'"></span>
            </x-button>

            <x-button type="button" wire:click="markAlpineReady" variant="primary">
                Confirm Livewire action
            </x-button>
        </div>

        <div x-show="open" x-cloak class="mt-4 rounded-md bg-slate-50 p-3 text-sm text-slate-700">
            Alpine.js is available on this page.
        </div>
    </div>

    @if ($alpineReady)
        <x-alert type="success" title="Livewire action OK">
            The Livewire component handled a server round-trip successfully.
        </x-alert>
    @endif
</div>
