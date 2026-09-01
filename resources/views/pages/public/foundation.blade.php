<x-layouts.public :title="'Foundation'">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Foundation smoke page</h1>
            <p class="mt-2 text-sm text-slate-600">
                Public layout with shared UI primitives, Livewire, Alpine.js, and Tailwind CSS.
            </p>
        </div>

        <livewire:smoke.foundation-smoke />

        <div
            x-data="{ showModal: false }"
            class="rounded-lg border border-slate-200 bg-white p-4"
        >
            <x-button type="button" variant="secondary" @click="showModal = true">
                Open modal sample
            </x-button>

            <div
                x-show="showModal"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                @keydown.escape.window="showModal = false"
            >
                <div class="absolute inset-0 bg-slate-900/40" @click="showModal = false"></div>
                <div class="relative w-full max-w-md rounded-lg border border-slate-200 bg-white p-5 shadow-lg">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <h2 class="text-base font-semibold text-slate-900">Sample dialog</h2>
                        <button type="button" class="rounded p-1 text-slate-500 hover:bg-slate-100" @click="showModal = false" aria-label="Close dialog">
                            ×
                        </button>
                    </div>
                    <p class="text-sm text-slate-700">This modal uses Alpine for open/close state.</p>
                    <div class="mt-4 flex justify-end">
                        <x-button type="button" variant="secondary" @click="showModal = false">Close</x-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.public>
