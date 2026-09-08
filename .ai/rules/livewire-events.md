---
paths:
  - 'app/Livewire/Events/**'
---

# Livewire Events

## Lifecycle UI: archived filtering + no authorize in mount
EBR-005: EventIndex excludes `archived` from the default (statusFilter '') listing; selecting the 'archived' status filter shows only archived, preserving historical access. EventPolicy lifecycle abilities (markOngoing, completeEvent, cancelEvent, archive) all share canOperateOnAssigned: Owner/Admin always, EventManager only when assigned. LifecycleActions is an embedded card (never a full page); it must NOT Gate::authorize in mount (recorded trap) — render() computes per-button can* booleans and each public method re-runs the action, which authorizes internally; ValidationException is caught and flashed as 'lifecycle_error'.
