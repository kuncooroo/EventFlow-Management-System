---
paths:
  - 'app/Http/Controllers/Public/**'
---

# Controllers Public

## Ticket secrets: URL token = ticket_code, QR payload = qr_token
tickets.public.show is keyed by the 26-char ticket_code (unpredictable ULID) shown on the page; the QR SVG encodes qr_token (64-hex random), which is the lookup token for check-in (TASK-022/023: resolve qr_token → ticket → registration). Both are unique in the DB. Reject tokens not matching `^[A-Za-z0-9]{26}$` with 404 before querying; lookup is rate-limited per-IP at 20/min via the `tickets` limiter. A cancelled registration's ticket page still renders but shows an invalid-notice, and IssueTicket refuses to issue a new ticket for a non-confirmed registration.
