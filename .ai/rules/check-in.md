---
paths:
  - 'app/Livewire/CheckIn/**'
---

# Check In

## Livewire result state: scalar + scanner fallbacks
CheckInResult is a PHP DTO with Eloquent relations — it CANNOT be a public Livewire property ("Property type not supported"). Components must map the DTO into scalar state: outcome/title/detail strings, rendered through livewire.check-in.partials.result-card. The CheckInScanner typed-code fallback is mandatory (never rely on BarcodeDetector existing); every scan consumes a rate-limited attempt (120/60s per event+user). Camera never meets the eyes: keep the typed-code form and manual search visible.
