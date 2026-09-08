---
paths:
  - 'app/Queries/Public/**'
---

# Public

## Public event visibility & CTA derivation
PublicEventQuery exposes only statuses reachable after publication (Published, Ongoing, Completed, Cancelled); Draft and Archived always 404. CTA state precedence in ctaState(): cancelled → registration disabled → scheduled (future starts_at) → closed (past ends_at) → sold-out (registrationCount >= capacity) → open. Registration count reads the `registrations` table only if it exists (Schema::hasTable guard); until the registration task lands it is 0. Sold-out is only reachable today via capacity = 0.
