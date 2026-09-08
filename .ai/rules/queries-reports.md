---
paths:
  - 'app/Queries/Reports/**'
---

# Queries Reports

## Report queries use SQL aggregates and qualify columns before joining
Operational reports (Registration/TicketType/Attendance) aggregate in SQL, not PHP collection math, and always scope to accessible events (AccessibleEventsQuery + ScopesToAccessibleEvents trait). When applying toBase() + joins, qualify every filter column with the table (e.g. registrations.event_id, registrations.registered_at) BEFORE any toBase() — joining ticket_types/events makes bare event_id ambiguous. MySQL aliases must not be SQL keywords (use type_id, not key). Attendance percentage is null when the confirmed denominator is 0.
