---
paths:
  - app/Policies/RegistrationPolicy.php
  - app/Policies/ReportPolicy.php
---

# Policies

## RegistrationPolicy/attendee authz delegates to EventPolicy + org 404
Attendee access is read-only view scoped by event. viewAny(user, Event)/view(user, Registration, Event) delegate to EventPolicy::view after verifying registration.event_id matches the route event. Route-mount components (AttendeeIndex/AttendeeShow) inject OrganizationContext and abort(404) on null or cross-org event before authorize(), matching EventSetup.

## ReportPolicy is model-less, registered class-to-class
ReportPolicy has no backing model. Register via Gate::policy(ReportPolicy::class, ReportPolicy::class) in AppServiceProvider::boot. Pages authorize with $this->authorize('viewAny', [ReportPolicy::class]); Gate drops the leading policy-class argument before invoking the method. view() does org equality + role check (Owner/Admin always; others need an event assignment for their current membership).
