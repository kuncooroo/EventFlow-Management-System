---
paths:
  - 'app/Actions/Tickets/**'
---

# Tickets

## IssueTicket idempotency must query, not reuse the relation accessor
Eloquent caches null HasOne results on the model, so checking `$registration->ticket` twice inside one request returns stale null after creation. IssueTicket's idempotency guard queries by registration_id directly, and calls setRelation('ticket', $ticket) after creating/returning so later relation access is correct.
