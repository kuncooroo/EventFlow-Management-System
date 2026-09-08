---
paths:
  - 'app/Notifications/**'
---

# Notifications

## Use the ShouldQueueAfterCommit contract, not a typed $afterCommit property
To defer a notification-driven job until the DB transaction commits, implement `Illuminate\Contracts\Queue\ShouldQueueAfterCommit` (alongside `ShouldQueue`) rather than declaring `public bool $afterCommit = true;` — the `Queueable` trait already declares an untyped `public $afterCommit`, so a typed redeclaration triggers a PHP fatal property-compatibility error. The constructed `Illuminate\Notifications\SendQueuedNotifications` job sets its own `afterCommit` from the contract.

## One combined confirmation email per registration; defer to after commit
Attendee emails must be a single email per registration (FR-NOT-002..003): `app/Listeners/SendRegistrationConfirmationEmail.php` sends `TicketAccessNotification` when `$registration->ticket` is loaded, otherwise `RegistrationConfirmationNotification`. The ticket mail carries that attendee's own ticket link (`route('tickets.public.show', ['token' => $ticket->ticket_code])`). Both notifications implement `ShouldQueue, ShouldQueueAfterCommit` and report failures via `failed(Throwable $e) { report($e); }` — a mail failure must never roll back/invalidate the registration (MAIL-001).
