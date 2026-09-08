---
paths:
  - 'app/Livewire/Attendees/**'
---

# Livewire Attendees

## Embedded action components must not authorize in mount
Livewire action components embedded in a detail page (e.g. CancelRegistrationAction inside attendee-show) must NOT call Gate::authorize in mount() — that 403s the entire parent page for lower-role viewers. Instead render a "permission required" state based on can() and re-authorize inside the action method only. Mount may still abort(404) on entity mismatch.
