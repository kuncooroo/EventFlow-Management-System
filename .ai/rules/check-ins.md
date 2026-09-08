---
paths:
  - 'app/Actions/CheckIns/**'
---

# Check Ins

## Check-in: audit naming and never log qr_token
RecordActivity action strings for CheckInAttendee are exactly `checkin.manual` and `checkin.qr` (subject_type `registration`). The `qr_token` is a private credential — never write it to audit properties, logs, or responses; the action only records method, ticket_id, and checked_in_at. If more fields are ever added, keep the token out.

## CheckInAttendee: DTO results, lock + unique race guard
Check-in results are returned as CheckInResult (success/duplicate/invalid DTO), NOT thrown exceptions. Duplicate detection: check for an existing row on the same registration, plus `check_ins.registration_id` UNIQUE as the final race guard — catch QueryException where isUniqueViolation and convert to duplicate. Lock the registration row (lockForUpdate scoped by event_id) inside the wrapping DB transaction. Registration must be Confirmed; cancelled or ticket-mismatch → invalid. Rows are append-only (no updated_at).
