---
paths:
  - 'app/Console/Commands/**, app/Jobs/Notifications/**, app/Models/EventReminderSend.php'
---

# Models

## Idempotent scheduler reminders via event_reminder_sends ledger
`send:event-reminders` (routes/console.php daily 08:00 + withoutOverlapping) is idempotent per intended occurrence: inside a DB transaction it locks the event row (lockForUpdate), and only if no `event_reminder_sends(event_id, occurrence_key)` row exists writes one BEFORE dispatching jobs. occurrence_key = `reminder:{start_at unix}` (a reschedule creates exactly one new occurrence). Only confirmed registrations on eligible events get `SendEventReminderJob` (excluded: draft/completed/cancelled/archived events, cancelled registrations, and outside the window `start_at - reminder_hours_before <= now < start_at`). Never send reminder emails inline in the command — dispatch the queued job (failed() => report($e)) so each attendee mail retries independently.
