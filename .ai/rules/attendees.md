---
paths:
  - 'app/Livewire/Attendees/**, app/Queries/Attendees/**, resources/views/livewire/attendees/**'
---

# Attendees

## Derive check-in status; no CheckIn model until TASK-022
Check-in state is derived, never stored on registrations. Guard with Schema::hasTable('check_ins'); when the table is absent everything is "not checked in" and the checked-in filter '1' yields no rows. Do not create a CheckIn model or query a checked_in boolean column here — the check_ins table/owner belongs to TASK-022/023.
