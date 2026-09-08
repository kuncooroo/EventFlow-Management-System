/*
|--------------------------------------------------------------------------
| Frontend entry
|--------------------------------------------------------------------------
|
| Alpine.js is provided by Livewire on pages that include @livewireScripts.
| Add page-specific browser behavior here only when it is not Livewire-owned.
|
*/

document.documentElement.classList.add('js-ready');

document.addEventListener('alpine:init', () => {
    const cooldownMs = 3500;

    Alpine.data('qrScanner', (wire) => ({
        scanning: false,
        cameraError: false,
        detector: null,
        stream: null,
        frame: null,
        videoEl: null,

        init() {
            this.videoEl = this.$refs.video;

            if (! window.BarcodeDetector) {
                return;
            }

            this.detector = new window.BarcodeDetector({
                formats: ['qr_code'],
            });
        },

        async toggle() {
            if (this.scanning) {
                this.stop();
            } else {
                await this.start();
            }
        },

        async start() {
            this.cameraError = false;

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' },
                    audio: false,
                });
            } catch (error) {
                this.cameraError = true;
                return;
            }

            this.videoEl.srcObject = this.stream;
            this.scanning = true;
            this.frame = window.requestAnimationFrame(() => this.scanLoop());
        },

        stop() {
            this.scanning = false;

            if (this.frame) {
                window.cancelAnimationFrame(this.frame);
                this.frame = null;
            }

            if (this.stream) {
                this.stream.getTracks().forEach((track) => track.stop());
                this.stream = null;
            }
        },

        async scanLoop() {
            if (! this.scanning) {
                return;
            }

            try {
                const codes = await this.detector.detect(this.videoEl);

                if (codes.length > 0 && codes[0].rawValue) {
                    this.pauseAndDispatch(codes[0].rawValue);
                    return;
                }
            } catch (error) {
                // Camera frames may occasionally error while starting/stopping; keep scanning.
            }

            this.frame = window.requestAnimationFrame(() => this.scanLoop());
        },

        pauseAndDispatch(token) {
            this.stop();

            wire.scanToken(token);

            window.setTimeout(() => {
                if (! this.scanning && ! this.cameraError) {
                    this.start();
                }
            }, cooldownMs);
        },
    }));
});