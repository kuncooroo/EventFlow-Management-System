---
paths:
  - 'app/Queries/Dashboard/**'
---

# Dashboard

## Event Dashboard attendance denominator and metrics
EventDashboardQuery drives the per-event dashboard (/app/events/{event}/dashboard, EventDashboard Livewire). Attendance percentage numerator = confirmed registrations with a check-in; denominator = confirmed registrations only (never cancelled/abandoned, DASH-002); return null (page shows "Not available") when denominator is 0 (DASH-003). Ticket-type summary is one ordered query with scalar sub-selects (registrations_count, confirmed_count, checked_in_count) to avoid N+1. DASH-004: totals must match registration/attendance report views.
