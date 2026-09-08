---
paths:
  - 'app/Actions/Tickets/**, app/Actions/Registrations/**, database/factories/**'
---

# Factories

## Public codes are random 26-char hex, never ULID
ticket_code and registration_code come from bin2hex(random_bytes(13)) (26 lowercase hex chars, 104-bit, non-sequential & unpredictable). qr_token stays bin2hex(random_bytes(32)) (64 hex, 256-bit) and is the check-in secret — never put it in URLs/logs. Keep TicketFactory/RegistrationFactory in sync with the action generators. Do not use Str::ulid for any public-facing identifier (hardening pass Phase 10).
