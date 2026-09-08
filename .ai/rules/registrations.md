---
paths:
  - 'app/Actions/Registrations/**'
---

# Registrations

## CancelRegistration: capacity + audit + transition rules
Cancellation only from Confirmed (ValidationException "Only confirmed registrations can be cancelled."). Because RegistrationCapacityService counts only confirmed, cancel releases event/ticket-type capacity with no code change. Audit action must be exactly `registration.status_changed` (subjectType `registration`, properties previous_status/new_status, stored via RecordActivity). Checked-in registrations must be rejected (Schema::hasTable('check_ins') guard; branch inactive until TASK-023). Lock the registration row (lockForUpdate scoped by event_id) inside DB::transaction.
