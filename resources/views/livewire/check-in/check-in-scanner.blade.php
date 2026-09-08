<div class="space-y-4">

    @if ($resultOutcome)
        <div class="flex flex-wrap items-start gap-3">
            <div class="min-w-0 flex-1">
                @include('livewire.check-in.partials.result-card', [
                    'outcome' => $resultOutcome,
                    'title' => $resultTitle,
                    'detail' => $resultDetail,
                ])
            </div>
            <button
                wire:click="resetScanner"
                class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                Scan next guest
            </button>
        </div>
    @endif

    {{-- Camera scanner (when the browser supports it) --}}
    <div x-data="qrScanner($wire)" x-on:alpine:init="init()" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Scan QR Code</h3>
                <p class="mt-1 text-sm text-slate-600">Point the camera at the attendee's ticket QR code.</p>
            </div>
            <button
                @click="toggle()"
                x-show="window.BarcodeDetector"
                x-text="scanning ? 'Stop Camera' : 'Start Camera'"
                class="inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"></button>
        </div>

        <div class="px-5 py-4">
            <video x-ref="video" autoplay playsinline muted
                   x-show="scanning"
                   class="mx-auto h-56 w-full max-w-md rounded-md bg-slate-900 object-cover"
                   aria-label="Camera preview for QR scanning"></video>

            <p x-show="scanning" class="mt-3 text-center text-sm text-slate-600">Scanning… hold the QR code still inside the frame.</p>
            <p x-show="! scanning && window.BarcodeDetector" class="mt-3 text-center text-sm text-slate-600">Camera is off. Start it to scan, or type the code below.</p>
            <p x-show="! window.BarcodeDetector" class="mt-3 text-center text-sm text-slate-600">
                Camera scanning is not supported in this browser. Type the code below or use Manual Check-In.
            </p>
            <p x-show="cameraError" class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Camera permission was denied or is unavailable. The manual check-in and typed-code fallbacks below still work.
            </p>
        </div>
    </div>

    {{-- Typed / pasted token fallback (non-camera path is always available) --}}
    <form wire:submit="attempt" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="qr-token-input" class="block text-sm font-medium text-slate-700">Entry code or pasted QR value</label>
                <input
                    id="qr-token-input"
                    wire:model="qrToken"
                    type="text"
                    autocomplete="off"
                    placeholder="Paste or type the ticket code"
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                />
            </div>
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                Check In
            </button>
        </div>
    </form>
</div>