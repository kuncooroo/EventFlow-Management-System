---
paths:
  - 'app/Actions/Events/**'
---

# Events

## Event lifecycle: valid transitions, audit keys, timestamps
Event transitions follow BUSINESS_FLOW §4.1.1 exactly: Draft→Published/Cancelled, Published→Ongoing/Cancelled, Ongoing→Completed/Cancelled, Completed|Cancelled→Archived. Anything else throws ValidationException key 'lifecycle' (PublishEvent keeps its own 'publish' key). Set the matching timestamp (started_at/completed_at/cancelled_at/archived_at). Audit actions: event.started, event.completed, event.cancelled, event.archived — subject 'event', properties include previous_status and new_status. Gate inside handle via the EventPolicy ability. Cancel does NOT delete registrations/tickets/agenda — RegisterAttendee already rejects Cancelled so new registrations stop without extra work.
