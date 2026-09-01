# EventFlow Management System — Development Roadmap

**Document Path:** `docs/ROADMAP.md`  
**Product:** EventFlow Management System  
**Document Type:** Technical Development Roadmap  
**Authoring Role:** Technical Project Manager  
**Primary Product Source:** `docs/PRD.md`  
**Technical Sources:** `docs/SRS.md`, `docs/SYSTEM_DESIGN.md`, `docs/BUSINESS_FLOW.md`, `docs/DATABASE.md`, `docs/UI_UX.md`, `CURSOR.md`  
**Architecture:** Laravel Modular Monolith  
**Target Stack:** Laravel 13.x · PHP 8.4.x · MySQL 8.4.x · Blade · Livewire · Alpine.js · Tailwind CSS  
**Deployment:** VPS  
**Roadmap Version:** 1.0  

---

# 1. Roadmap Purpose

This roadmap converts the EventFlow product, architecture, business workflow, database, and UI/UX specifications into an ordered development program.

The roadmap is designed to:

- minimize rework,
- preserve business-rule consistency,
- deliver a commercially usable MVP,
- keep implementation testable at every stage,
- avoid premature infrastructure,
- allow source-code and self-hosted distribution,
- preserve a clean path toward future SaaS commercialization.

Each phase defines:

1. Objectives
2. Modules
3. Dependencies
4. Deliverables
5. Acceptance Criteria
6. Testing Requirements

A phase is not complete merely because screens or database tables exist. A phase is complete only when its acceptance and testing gates pass.

---

# 2. Delivery Principles

## 2.1 Build Vertical Capability, Not Isolated Screens

Whenever practical, phases should produce a usable end-to-end slice.

Example:

```text
Database
→ Model
→ Policy
→ Business Action
→ Livewire/UI
→ Test
```

Do not build the entire database first and postpone validation, authorization, and testing indefinitely.

## 2.2 Follow Dependency Order

The recommended dependency chain is:

```text
Foundation
    ↓
Authentication
    ↓
Users / Organizations / Roles
    ↓
Core Event Master Data
    ↓
Primary Event Workflow
    ↓
Secondary Event Operations
    ↓
Financial Extension
    ↓
Reporting
    ↓
Notifications
    ↓
Settings
    ↓
Security Hardening
    ↓
Full Testing
    ↓
Installer
    ↓
Demo
    ↓
Documentation
    ↓
Release
```

Some work may overlap, but downstream phases must not bypass required upstream acceptance gates.

## 2.3 Protect MVP Scope

The MVP golden path remains:

```text
Create Event
→ Publish
→ Registration
→ Attendee
→ Ticket / QR
→ Check-In
→ Attendance
→ Report
```

Features outside this path should not delay the commercial MVP unless explicitly required.

## 2.4 Financial Module Scope Warning

The authoritative PRD defines online payment as **out of mandatory MVP scope**.

Therefore:

- Phase 6 is a post-core commercial extension.
- Informational ticket pricing/currency may exist earlier.
- Payment gateway, orders, invoices, refunds, and settlement must not be implemented without an approved PRD/business-flow update.

## 2.5 Testing Is Continuous

Phase 11 is the dedicated system-wide testing and hardening phase.

It does **not** mean earlier phases can skip tests.

Every phase must include tests for the code introduced in that phase.

---

# 3. Phase Gate Model

Each development phase has three gates.

## Gate A — Implementation Complete

- required modules exist,
- required UI exists,
- business workflows are wired.

## Gate B — Functional Acceptance

- acceptance criteria pass,
- role restrictions work,
- error states work.

## Gate C — Technical Quality

- tests pass,
- no known critical data-integrity issue exists,
- no unresolved security blocker exists,
- documentation impacted by the phase is current.

Only then should the phase be considered complete.

---

# 4. Phase 0 — Project Foundation

## Objectives

Establish a verified, reproducible Laravel project foundation that all later phases can safely build upon.

Primary goals:

- initialize the Laravel application,
- verify actual runtime/package versions,
- establish project structure,
- configure development/test environments,
- establish code quality and engineering conventions,
- prepare the shared SaaS UI shell,
- establish CI-ready testing commands,
- create the initial database infrastructure.

## Modules

- Laravel application bootstrap
- Environment configuration
- Database connection
- Blade application shell
- Tailwind setup
- Livewire setup
- Alpine.js setup
- Base layout
- Shared UI primitives
- Base route structure
- Base exception/logging configuration
- Test environment
- Framework infrastructure migrations
- Project documentation integration

## Dependencies

None.

This is the root phase.

## Deliverables

1. Initialized Laravel repository.
2. Verified:
   - Laravel version,
   - PHP version,
   - MySQL version,
   - Livewire version,
   - Alpine.js version,
   - Tailwind version.
3. `composer.lock` committed.
4. Frontend lockfile committed.
5. `.env.example` complete without credentials.
6. Working local development environment.
7. Working test database/environment.
8. Application shell:
   - authenticated layout placeholder,
   - public layout placeholder.
9. Shared UI components:
   - button,
   - input,
   - form error,
   - badge,
   - alert,
   - modal/dialog,
   - empty state,
   - pagination style.
10. Base logging configured.
11. Base queue/session/cache configuration documented.
12. Laravel infrastructure tables generated from actual installed framework where drivers require them.
13. `CURSOR.md` present at repository root.
14. Existing architecture documentation retained under `docs/`.

## Acceptance Criteria

- Application boots successfully.
- Database connection succeeds.
- Frontend assets compile successfully.
- Livewire component renders successfully.
- Alpine behavior works on a test component.
- Tailwind styles load correctly.
- Test suite can run successfully from a clean setup.
- No production credentials exist in repository.
- Actual installed stack versions are documented.
- Application structure follows `CURSOR.md`.
- No unnecessary third-party architecture package has been installed.

## Testing Requirements

### Automated

- framework smoke test,
- database connection test through test suite,
- one Livewire rendering test,
- one public page rendering test.

### Manual

- fresh clone/setup procedure tested,
- frontend build verified,
- local login placeholder route accessibility checked,
- responsive base shell checked.

### Quality Gate

No later business module begins until:

```text
application boot
+
database
+
frontend build
+
tests
```

are reproducible.

---

# 5. Phase 1 — Authentication

## Objectives

Deliver secure authenticated access for organizer-side users.

This phase establishes identity but does not yet implement complete organization permissions.

## Modules

- Login
- Logout
- Forgot Password
- Reset Password
- User profile foundation
- Session management
- Login throttling
- Authentication middleware

## Dependencies

- Phase 0

## Deliverables

1. `users` persistence.
2. Authentication pages consistent with `docs/UI_UX.md`.
3. Login workflow.
4. Logout workflow.
5. Forgot-password workflow.
6. Reset-password workflow.
7. Session regeneration after login.
8. Authentication middleware on protected route group.
9. Base profile page.
10. Authentication test factories.
11. Mail fake support for password-reset tests.

## Acceptance Criteria

- Valid user can log in.
- Invalid credentials are rejected using safe feedback.
- Authenticated session is created/regenerated.
- Protected route redirects unauthenticated user.
- Logout invalidates active session.
- Password-reset request works.
- Expired/invalid reset token is rejected.
- Successful reset changes the password.
- Authentication pages are responsive.
- No public organizer self-registration page is introduced unless PRD is explicitly changed.

## Testing Requirements

### Feature Tests

- valid login,
- invalid password,
- unknown/invalid login behavior,
- logout,
- protected route,
- password reset request,
- valid reset,
- invalid/expired reset.

### Security Tests

- session fixation protection through regeneration,
- login throttling behavior,
- no sensitive credentials in response/log.

### UI Tests

- keyboard navigation,
- validation feedback,
- mobile authentication layout.

---

# 6. Phase 2 — Users / Roles / Permissions

## Objectives

Implement EventFlow's organization-scoped access model and fixed MVP roles.

This phase creates the tenant/access foundation required before event data is introduced.

## Modules

- Organizations
- Organization memberships
- Organization invitations
- Fixed roles:
  - Owner
  - Admin
  - Event Manager
  - Staff
  - Viewer
- Policies/Gates
- Organization switcher
- Member management
- Event assignment foundation
- Audit foundation for membership actions

## Dependencies

- Phase 0
- Phase 1

## Deliverables

1. `organizations`.
2. `organization_memberships`.
3. `organization_invitations`.
4. `event_assignments` schema/foundation where appropriate.
5. Organization creation workflow.
6. Organization selection/switcher.
7. Member list.
8. Invite Member workflow.
9. Role assignment/change.
10. Member removal.
11. Owner-protection rule.
12. Organization-level Policies.
13. Initial activity logging mechanism.
14. Role-aware sidebar/navigation.
15. Organization-scoped route structure.

## Acceptance Criteria

- User can belong to an organization.
- Authorized Owner/Admin can invite/add a member.
- Existing user can be linked without duplicate user account.
- Membership role is stored at organization level.
- Last active Owner cannot be removed/demoted.
- Removing membership revokes future access.
- User from Organization A cannot access Organization B private data.
- Staff/Viewer cannot perform Owner-only actions.
- Role change is audited.
- Member removal is audited.
- Organization switcher shows only accessible organizations.

## Testing Requirements

### Authorization Matrix Tests

For each relevant operation, test:

- Owner
- Admin
- Event Manager
- Staff
- Viewer

### Isolation Tests

Attempt cross-organization access using:

- URL manipulation,
- direct route/model ID,
- Livewire action,
- list query.

### Transaction Tests

- two concurrent/competing Owner changes must not leave zero Owners.

### Invitation Tests

- valid invitation,
- expired invitation,
- duplicate membership,
- revoked invitation,
- invalid role.

---

# 7. Phase 3 — Core Master Data

## Objectives

Create the foundational event data structures and CRUD required before attendee transactions can begin.

The phase focuses on event configuration/master data, not yet the complete public registration transaction.

## Modules

- Events
- Event lifecycle foundation
- Venues
- Agenda Items
- Registration field definitions
- Ticket types
- Media/file references
- Event assignments
- Event setup UI

## Dependencies

- Phase 0
- Phase 1
- Phase 2

## Deliverables

### Events

- event create,
- event edit,
- Draft state,
- event list,
- event detail,
- event status badge,
- event assignment access.

### Venues

- mode:
  - online,
  - offline,
  - hybrid,
- venue name,
- address,
- notes,
- public visibility.

### Agenda

- create/edit agenda item,
- date/time,
- location,
- speaker text,
- sort order.

### Registration Configuration

- registration enabled,
- registration start/end,
- require phone,
- require organization,
- event capacity.

### Custom Fields

- text,
- textarea,
- select,
- radio,
- checkbox,
- date,
- required/optional,
- active/inactive,
- ordering.

### Ticket Types

- name,
- description,
- informational price,
- currency,
- capacity,
- availability window,
- active/inactive,
- order.

### Media

- organization logo foundation,
- event banner,
- validated upload,
- file metadata.

## Acceptance Criteria

- Authorized user can create an event.
- New event starts as Draft.
- Event belongs to current organization.
- Unauthorized users cannot edit event.
- Event Manager is restricted according to event assignment rules.
- Event configuration validates start/end dates.
- Agenda item validates required title/start time.
- Ticket type capacity cannot be invalid/negative.
- Ticket availability period validates correctly.
- Custom fields use only supported field types.
- Deactivated field remains available to historical data design.
- Event banner upload rejects invalid type/oversized files.
- N+1 queries are absent from event lists.
- Large event lists are paginated.

## Testing Requirements

### CRUD Tests

For:

- events,
- venue,
- agenda,
- registration fields,
- ticket types.

### Authorization Tests

Cross-role and cross-organization.

### Validation Tests

- invalid dates,
- invalid capacities,
- invalid field type,
- invalid price/currency relationship,
- invalid upload.

### File Tests

Use fake filesystem.

### Database Tests

- unique public slug behavior where generated/assigned,
- FK integrity,
- ticket-type uniqueness per event if implemented as specified.

---

# 8. Phase 4 — Primary Business Workflow

## Objectives

Deliver the EventFlow MVP's most important end-to-end business transaction:

```text
Draft Event
→ Publish
→ Public Registration
→ Registration Record
→ Ticket
→ QR
→ Check-In
```

This is the highest-risk and highest-value development phase.

## Modules

- Event publication
- Public event page
- Registration availability
- Public registration form
- Registration transaction
- Custom registration answers
- Ticket issuance
- QR token
- Attendee list
- Attendee detail
- Registration cancellation
- Manual check-in
- QR check-in
- Event lifecycle transitions
- Activity audit for critical operations

## Dependencies

Mandatory:

- Phase 0
- Phase 1
- Phase 2
- Phase 3

## Deliverables

### Event Publication

- publish readiness validation,
- Draft → Published,
- public event URL,
- publication audit.

### Public Pages

- public event overview,
- agenda,
- ticket type display,
- registration CTA states:
  - open,
  - scheduled,
  - closed,
  - sold out,
  - cancelled.

### Registration

- default attendee fields,
- custom field answers,
- capacity-safe transaction,
- unique registration code,
- `confirmed` registration state,
- confirmation screen.

### Ticket

- one ticket per registration,
- unique ticket code,
- unique high-entropy QR token,
- mobile ticket page.

### Attendee Management

- attendee table,
- search,
- status display,
- ticket display,
- attendee detail.

### Cancellation

- Confirmed → Cancelled derived flow,
- check-in eligibility invalidation,
- capacity policy implementation after product decision,
- audit.

### Check-In

- QR scanner UI,
- manual search fallback,
- atomic check-in,
- duplicate detection,
- invalid-ticket state,
- wrong-event state,
- audit.

### Lifecycle

- Published → Ongoing
- Ongoing → Completed
- Draft/Published/Ongoing → Cancelled
- Completed/Cancelled → Archived

## Acceptance Criteria

This phase must satisfy the core PRD acceptance criteria.

### Publication

- incomplete Draft cannot publish,
- valid Draft can publish,
- public URL only exposes Published/appropriate public state.

### Registration

- valid registration creates exactly one registration,
- invalid form creates none,
- closed registration creates none,
- sold-out ticket creates none,
- capacity cannot be exceeded.

### Ticket

- ticket code unique,
- QR token unique,
- one ticket per ticket-enabled registration,
- another attendee's ticket cannot be accessed by guessing IDs.

### Check-In

- valid ticket checks in once,
- duplicate scan does not create second attendance row,
- invalid QR creates no attendance,
- cancelled registration cannot check in,
- wrong-event ticket is rejected,
- manual check-in uses the same eligibility rules.

### Lifecycle

- invalid status transitions are rejected,
- cancellation blocks new registration,
- completed event remains reportable,
- archive hides from active list.

## Testing Requirements

### Mandatory Concurrency Tests

1. Two registrations compete for the final event capacity.
2. Two registrations compete for the final ticket-type capacity.
3. Two simultaneous check-ins use the same ticket.

Expected:

```text
No overselling
No duplicate attendance
```

### End-to-End Feature Tests

- organizer creates/configures/publishes event,
- attendee registers,
- ticket generated,
- staff checks in attendee,
- event completes.

### Authorization Tests

All management actions across roles.

### Public Security Tests

- sequential ID guessing does not expose ticket/private registration,
- Draft event registration denied,
- cancelled event registration denied.

### Audit Tests

Verify required critical events are recorded.

---

# 9. Phase 5 — Secondary Modules

## Objectives

Complete the non-financial supporting event-operations experience around the primary workflow.

This phase improves usability, operational coverage, and commercial completeness without introducing unrelated enterprise complexity.

## Modules

- Enhanced attendee management
- Event agenda refinement
- Registration field management refinement
- Event duplication — optional professional feature
- Waiting list — optional/deferred
- Speaker management — optional professional feature
- Certificate — optional professional feature
- Survey/feedback — optional professional feature
- Extended activity history
- Operational quick actions

## Dependencies

Mandatory:

- Phase 4

Optional modules may depend on:

- Phase 7 for report integration,
- Phase 8 for notification integration.

## Deliverables

### Required Secondary MVP Polish

- attendee filtering,
- attendee status actions,
- recent registrations,
- recent check-ins,
- event setup checklist,
- lifecycle UX improvements,
- activity-log view for permitted users.

### Optional Professional Increment

Only if explicitly included in release scope:

- Event Clone
- Waiting List
- Speaker records
- Certificate generation
- Survey

These features must not be created as empty placeholder navigation.

## Acceptance Criteria

For required secondary functionality:

- attendee filtering works by:
  - status,
  - ticket type,
  - check-in state,
  - date.
- filters never expose other organizations.
- event setup checklist reflects actual configuration.
- activity history displays required actions newest-first.
- Staff sees only relevant operational actions.
- Archived/cancelled event UI reflects read-oriented lifecycle correctly.

For optional modules:

- each must have approved product behavior before development,
- no optional module may silently change primary registration/check-in semantics.

## Testing Requirements

- filter combinations,
- pagination,
- event assignment restrictions,
- activity log access,
- lifecycle UI behavior,
- mobile attendee cards,
- regression tests for primary workflow.

Optional modules require their own feature/authorization tests.

---

# 10. Phase 6 — Financial Modules

## Objectives

Provide a controlled expansion path for paid events **after explicit product approval**.

The current PRD does not require payment processing for MVP.

Therefore this phase begins with a **Product Scope Gate**.

## Modules

### Already Supported Before This Phase

- informational ticket price,
- currency.

### Future Financial Modules — Only After Approval

- Orders
- Payments
- Payment Gateway Adapter
- Payment Status
- Invoices
- Refunds
- Coupons/Promo Codes
- Financial Reporting

## Dependencies

Mandatory:

- Phase 4
- approved change to:
  - PRD,
  - SRS,
  - BUSINESS_FLOW,
  - DATABASE,
  - SYSTEM_DESIGN

Payment implementation must not begin before those documents define:

- payment lifecycle,
- registration confirmation semantics,
- refund rules,
- idempotency,
- gateway webhook behavior,
- reconciliation.

## Deliverables

If payment remains out of release scope:

1. No payment code is introduced.
2. Informational price/currency works correctly.
3. Roadmap records Phase 6 as deferred.

If payment is approved:

1. payment architecture document update,
2. order/payment tables,
3. gateway-independent payment interface,
4. one initially supported gateway,
5. secure webhook verification,
6. idempotent payment update,
7. invoice/receipt behavior if required,
8. refund flow if required,
9. financial permissions,
10. financial audit trail.

## Acceptance Criteria

### Deferred Scenario

- Core EventFlow works without payment provider.
- Positive ticket price does not falsely imply online payment completion.
- UI clearly represents the actual payment capability.

### Approved Payment Scenario

Must define and pass:

- no duplicate charge from repeated callback,
- no fake `paid` state from client request,
- webhook signature verified,
- order/payment transition valid,
- registration/payment dependency matches approved business rule,
- refund transition auditable,
- currency/decimal calculations exact.

## Testing Requirements

If deferred:

- ticket price rendering,
- currency validation,
- no payment UI/routes accidentally exposed.

If implemented:

- gateway fake tests,
- webhook signature tests,
- duplicate webhook/idempotency tests,
- payment failure tests,
- timeout tests,
- refund tests,
- transaction tests,
- authorization tests,
- financial reconciliation tests.

Never call a live payment provider in automated tests.

---

# 11. Phase 7 — Reporting

## Objectives

Deliver management visibility and reliable operational reporting from authoritative EventFlow data.

## Modules

- Organization dashboard
- Event dashboard
- Registration report
- Ticket type report
- Attendance report
- CSV export
- Filtered export
- Dashboard aggregate queries

## Dependencies

Mandatory:

- Phase 4

Recommended:

- Phase 5 required secondary data features completed.

If financial reports are included:

- Phase 6 must be approved and completed.

## Deliverables

### Organization Dashboard

- active events,
- upcoming events,
- registrations summary,
- checked-in summary.

### Event Dashboard

- event status,
- registration state,
- total registrations,
- confirmed registrations,
- checked-in total,
- attendance percentage,
- ticket-type summary,
- recent registrations,
- recent check-ins.

### Reports

- event summary,
- registrations,
- ticket types,
- attendance.

### Export

- attendee CSV,
- registration CSV,
- attendance CSV,
- filtered export.

## Acceptance Criteria

- dashboard totals reconcile with report totals.
- attendance percentage uses approved denominator.
- zero-attendee event does not cause divide-by-zero/error.
- reports are organization/event scoped.
- Viewer sees read-only permitted information.
- exports contain only authorized fields.
- filtered export respects current filters.
- 10,000-row CSV export does not exhaust normal browser/server memory.
- no N+1 in report/detail rendering.
- dashboard meets SRS performance targets under representative data.

## Testing Requirements

### Data Reconciliation Tests

Seed known records and verify exact totals.

### Authorization Tests

- role access,
- cross-organization denial,
- export permissions.

### Performance Tests

Representative:

- 10,000 attendees/event,
- 100 events,
- 100,000 historical registrations benchmark context.

### Export Tests

- CSV headers,
- row count,
- filtered rows,
- timezone formatting,
- unauthorized fields excluded.

---

# 12. Phase 8 — Notifications

## Objectives

Add reliable transactional communications without coupling external mail delivery to critical business state.

## Modules

- Registration confirmation
- Ticket email/access email
- Organization invitation email
- Event reminder
- Queue processing
- Failed job handling
- Email templates
- Delivery troubleshooting foundation

## Dependencies

- Phase 1 for password/reset email
- Phase 2 for invitations
- Phase 4 for registration/ticket
- Phase 0 queue/mail configuration

## Deliverables

1. Queueable notifications.
2. Registration confirmation template.
3. Ticket-access template.
4. Invitation template.
5. Event reminder template.
6. Scheduler-driven reminder flow.
7. Queue worker configuration.
8. Failed-job visibility.
9. Safe development/testing mail setup.
10. Optional delivery-status presentation if required.

## Acceptance Criteria

- registration remains successful if email fails.
- email jobs dispatch after DB commit.
- invitation email references the correct organization.
- ticket email exposes only intended attendee ticket.
- reminder does not send for ineligible/cancelled event.
- repeated scheduler run does not send duplicate intended reminder occurrence.
- failed jobs are observable.
- mail provider details are environment-configured.

## Testing Requirements

Use:

- Mail fake,
- Notification fake,
- Queue fake where appropriate.

Test:

- confirmation dispatched,
- ticket recipient correct,
- failure does not roll back registration,
- after-commit behavior,
- reminder idempotency,
- cancelled-event reminder suppression.

---

# 13. Phase 9 — Settings

## Objectives

Complete organization and account configuration required for commercial use while avoiding an unstructured settings system.

## Modules

- Organization General Settings
- Branding
- Localization
- Profile refinements
- Organization logo
- Timezone
- Locale
- Default currency
- Extensible organization settings foundation

## Dependencies

- Phase 2
- Phase 3
- Phase 8 if notification settings are included

## Deliverables

### General

- organization name,
- default currency where applicable.

### Branding

- organization logo,
- basic branding preview.

### Localization

- timezone,
- locale.

### Profile

- name,
- email/account information according to approved auth design.

### Settings Infrastructure

- `organization_settings` only for appropriate extensible values,
- first-class core settings remain typed columns.

## Acceptance Criteria

- only authorized roles may edit organization settings.
- timezone selection uses valid timezone values.
- locale accepts only supported locales.
- currency uses valid 3-character code.
- organization logo upload validates type/size.
- restricted settings changes are audited.
- Staff cannot mutate organization settings.
- settings do not leak across organizations.

## Testing Requirements

- authorization,
- validation,
- organization isolation,
- file upload,
- audit,
- timezone display behavior,
- locale fallback behavior.

---

# 14. Phase 10 — Security

## Objectives

Perform dedicated application security hardening before final system test and commercial packaging.

Security is practiced in all phases; this phase performs explicit cross-system review.

## Modules / Areas

- Authentication security
- Authorization review
- Organization isolation
- CSRF
- XSS
- SQL injection protection
- Upload security
- Session settings
- Rate limiting
- Ticket token security
- Public route exposure
- Production error behavior
- Secret/config handling
- Log sanitization
- Security headers/reverse-proxy considerations
- Backup access
- Dependency review

## Dependencies

- Phases 1–9 as applicable

## Deliverables

1. Security review checklist.
2. Organization isolation test suite.
3. Public route inventory.
4. Protected route inventory.
5. Rate-limit policy.
6. Upload security validation.
7. Production debug verification.
8. Sensitive log review.
9. Secret/configuration review.
10. Dependency audit.
11. Session/cookie configuration review.
12. Ticket/QR token entropy review.
13. Backup access policy.

## Acceptance Criteria

- Organization A cannot access Organization B data through any tested path.
- Protected mutations always invoke server authorization.
- client-side validation bypass does not bypass server validation.
- user-generated output is escaped.
- raw user HTML is not rendered unsafely.
- uploads reject invalid/oversized content.
- application does not expose stack traces in production mode.
- secrets do not exist in repository.
- QR/ticket identifiers are not sequential/predictable.
- password/reset/session secrets are not logged.
- login/public high-risk endpoints have appropriate rate limiting.
- dependency vulnerabilities have been reviewed and critical issues resolved.

## Testing Requirements

### Security Feature Tests

- IDOR/cross-tenant tests,
- unauthorized Livewire action tests,
- CSRF behavior,
- upload validation,
- ticket URL access,
- role escalation attempts.

### Manual Review

- repository secret scan,
- production configuration,
- HTTP response inspection,
- error-page inspection,
- log inspection.

---

# 15. Phase 11 — Testing

## Objectives

Execute system-wide quality assurance and regression testing against PRD acceptance criteria.

This phase is not the start of testing; it is the final integrated test campaign.

## Modules / Test Domains

- Authentication
- Organizations
- Roles
- Events
- Venue
- Agenda
- Registration
- Ticketing
- Attendees
- Check-In
- Reports
- Notifications
- Settings
- Files
- Audit
- Security
- Responsive UI
- Accessibility
- Performance
- Backup/restore

## Dependencies

- All release-scope feature phases complete
- Phase 10 security hardening

## Deliverables

1. PRD acceptance-test matrix.
2. Automated regression suite.
3. Role-permission test matrix.
4. Cross-organization isolation suite.
5. Concurrency test suite.
6. Performance test dataset.
7. Browser/mobile QA checklist.
8. Accessibility QA checklist.
9. Backup restore test result.
10. Defect list with severity.
11. Release-blocker tracking.

## Acceptance Criteria

At minimum, PRD AC-01 through AC-18 must pass.

No unresolved:

- Critical defect
- Critical security issue
- Known data-corruption issue
- Known cross-organization data leak
- Capacity overselling bug
- Duplicate check-in integrity bug

may remain.

## Testing Requirements

### Automated

- unit,
- feature,
- integration,
- Livewire,
- queue/mail/file fakes.

### Concurrency

- final capacity registration,
- duplicate check-in,
- critical ownership changes.

### Performance

Validate SRS targets.

### Manual

- desktop browser,
- mobile registration,
- mobile check-in,
- camera permission fallback,
- keyboard navigation,
- responsive layouts.

### Accessibility

- visible focus,
- labels,
- modal focus,
- non-color-only statuses,
- keyboard check-in fallback.

---

# 16. Phase 12 — Installer

## Objectives

Make EventFlow reliably installable by commercial self-hosted/source-code customers on supported VPS environments.

The installer should reduce deployment friction without weakening security.

## Modules

- Installation pre-flight
- Environment requirements check
- Environment configuration guidance
- Database setup
- Migration execution
- Admin/Owner bootstrap
- Storage setup
- Queue/scheduler setup instructions
- Installation completion
- Health verification

## Dependencies

- Stable release-scope schema
- Phase 11 test baseline
- Deployment requirements from SRS

## Deliverables

At minimum:

1. documented installation workflow.
2. environment requirements checker or documented pre-flight commands.
3. database configuration procedure.
4. migration procedure.
5. first organization/Owner bootstrap workflow.
6. storage link/storage permission instructions.
7. queue worker setup.
8. cron/scheduler setup.
9. mail configuration instructions.
10. production cache/optimization instructions.
11. post-install health/smoke check.

A web-based installation wizard is optional, not mandatory.

## Acceptance Criteria

On a clean supported VPS/environment, an installer/operator can:

- configure application,
- connect database,
- run migrations,
- create initial Owner,
- access login,
- create first organization/event,
- upload a test asset,
- process a queued test job or confirm worker setup,
- invoke scheduler correctly.

Installation does not:

- expose `.env`,
- print secrets publicly,
- use default production credentials,
- leave debug mode enabled.

## Testing Requirements

- fresh installation test,
- reinstall/idempotency behavior where relevant,
- missing PHP extension handling,
- invalid DB credentials handling,
- file-permission failure handling,
- queue-worker verification,
- scheduler verification,
- production-mode check.

---

# 17. Phase 13 — Demo System

## Objectives

Create a safe commercial demo environment that demonstrates EventFlow's value without exposing real customer data or allowing destructive shared-environment abuse.

## Modules

- Demo mode banner
- Demo organization
- Demo users/roles
- Synthetic events
- Synthetic registrations
- Synthetic check-ins
- Demo reset
- Restricted dangerous actions

## Dependencies

- Phase 11
- Phase 12 useful but not strictly required
- stable seeded data model

## Deliverables

1. synthetic demo seeder.
2. sample organization.
3. sample users for relevant roles.
4. realistic sample events.
5. sample attendee/ticket/check-in data.
6. visible Demo Mode notice.
7. dangerous action restrictions.
8. reset process.

Recommended demo event examples:

- Technology Conference
- Digital Marketing Workshop
- Campus Career Fair

## Acceptance Criteria

- demo contains no real customer data.
- demo users cannot change production/system credentials.
- demo Owner cannot permanently destroy the shared demo environment.
- key flows can be explored:
  - dashboard,
  - event,
  - attendees,
  - reports,
  - check-in UI.
- reset restores expected baseline.
- demo restrictions are centralized and understandable.

## Testing Requirements

- demo seeding from clean DB,
- reset test,
- dangerous action restriction,
- role access,
- no real mail delivery unless explicitly routed to safe sink,
- no production secrets available through demo UI.

---

# 18. Phase 14 — Documentation

## Objectives

Produce complete commercial, operational, and developer documentation aligned with the final implementation.

## Modules / Documents

Existing design documents:

- PRD
- SRS
- SYSTEM_DESIGN
- BUSINESS_FLOW
- DATABASE
- UI_UX
- CURSOR

Commercial/operational documentation to finalize:

- Installation Guide
- Configuration Guide
- User Guide
- Deployment Guide
- Backup & Restore Guide
- Upgrade Guide
- Troubleshooting Guide
- Release Notes
- Changelog
- License/usage documentation as applicable

## Dependencies

- Stable release candidate behavior
- Phase 12 Installer
- Phase 13 Demo if demo docs are included

## Deliverables

1. updated architecture docs matching implementation.
2. user documentation.
3. installation instructions.
4. environment-variable reference.
5. queue/scheduler guide.
6. backup/restore guide.
7. upgrade instructions.
8. troubleshooting section.
9. role/permission explanation.
10. event lifecycle explanation.
11. registration/check-in user instructions.
12. release notes template.

## Acceptance Criteria

A technically competent customer with supported VPS access can use documentation to:

- install EventFlow,
- configure it,
- create users/organization,
- create/publish event,
- operate registration/check-in,
- generate reports,
- configure mail,
- run queue/scheduler,
- back up/restore,
- perform a supported upgrade.

Documentation:

- does not contain real credentials,
- matches current UI,
- uses current route/feature names,
- clearly marks optional/deferred features.

## Testing Requirements

### Documentation Test

A clean-install documentation walkthrough must be performed.

### User Workflow Test

A person following the guide should be able to complete the golden path without developer knowledge.

### Link/Reference Review

Ensure referenced files, commands, and paths exist.

---

# 19. Phase 15 — Release Preparation

## Objectives

Prepare EventFlow for its first commercial release.

This phase turns a tested application into a controlled versioned product.

## Modules / Areas

- Versioning
- Release build
- Production configuration
- Dependency locks
- Migration review
- Upgrade/rollback
- Backup
- Licensing package
- Source-code distribution hygiene
- Demo verification
- Documentation verification
- Release notes
- Final smoke test

## Dependencies

All release-scope phases:

- Phase 0 through Phase 14
- Phase 6 only if financial functionality is included in the release

## Deliverables

1. versioned release candidate.
2. finalized `composer.lock`.
3. finalized frontend lockfile.
4. reviewed migrations.
5. production `.env.example`.
6. installation package/process.
7. upgrade notes.
8. changelog.
9. release notes.
10. tested demo build.
11. backup/restore confirmation.
12. license/commercial packaging files.
13. final source-code archive/repository tag.
14. release checklist.
15. known limitations list.

## Acceptance Criteria

### Product

- golden path works end-to-end.
- PRD acceptance criteria pass.
- all release modules are accessible only to correct roles.

### Technical

- tests pass.
- production build succeeds.
- migrations succeed from clean DB.
- supported upgrade path succeeds from previous supported version when applicable.
- queue/scheduler operate.
- debug disabled.
- no credentials committed.
- no critical security issue.
- no N+1 blocker on primary pages.
- performance criteria pass.

### Commercial

- installation docs complete.
- user guide complete.
- demo works.
- package contains no development-only secrets/data.
- version and changelog are consistent.

## Testing Requirements

### Final Smoke Test

Run on production-like environment:

```text
Install
→ Login
→ Create Organization
→ Invite/Assign Member
→ Create Event
→ Configure
→ Publish
→ Register Attendee
→ View Ticket
→ Check In
→ Complete Event
→ View Report
→ Export CSV
```

### Upgrade Test

When applicable:

```text
Supported previous release
→ backup
→ deploy new release
→ run migrations
→ restart workers
→ smoke test
```

### Rollback Review

Verify rollback limitations are documented, especially when schema changes are not backward reversible.

---

# 20. Phase Dependency Matrix

| Phase | Name | Primary Dependencies |
|---|---|---|
| 0 | Project Foundation | None |
| 1 | Authentication | 0 |
| 2 | Users/Roles/Permissions | 0, 1 |
| 3 | Core Master Data | 0, 1, 2 |
| 4 | Primary Business Workflow | 0–3 |
| 5 | Secondary Modules | 4 |
| 6 | Financial Modules | 4 + Product Approval |
| 7 | Reporting | 4; 6 only for financial reports |
| 8 | Notifications | 1, 2, 4 |
| 9 | Settings | 2, 3 |
| 10 | Security | Release-scope features substantially complete |
| 11 | Testing | 10 + release-scope feature phases |
| 12 | Installer | Stable schema + 11 |
| 13 | Demo System | 11; installer recommended |
| 14 | Documentation | Stable product + 12/13 as applicable |
| 15 | Release Preparation | All release-scope phases |

---

# 21. Module-to-Phase Mapping

| Module | Primary Phase |
|---|---|
| Laravel/Foundation | 0 |
| Authentication | 1 |
| Users | 1–2 |
| Organizations | 2 |
| Membership/Roles | 2 |
| Invitations | 2 |
| Policies | 2 onward |
| Events | 3 |
| Venue | 3 |
| Agenda | 3 |
| Registration Fields | 3 |
| Ticket Types | 3 |
| Public Event Page | 4 |
| Registration | 4 |
| Tickets / QR | 4 |
| Attendees | 4–5 |
| Check-In | 4 |
| Event Lifecycle | 4 |
| Activity Log | 2 foundation, expanded 4–5 |
| Secondary Professional Features | 5 |
| Payment | 6, only after approval |
| Reports | 7 |
| CSV Export | 7 |
| Notifications | 8 |
| Settings | 9 |
| Security Hardening | 10 |
| System QA | 11 |
| Installer | 12 |
| Demo | 13 |
| Documentation | 14 |
| Commercial Release | 15 |

---

# 22. MVP Release Boundary

The recommended **minimum commercial MVP** includes:

```text
Phase 0
Phase 1
Phase 2
Phase 3
Phase 4
Required portion of Phase 5
Phase 7
Phase 8
Phase 9
Phase 10
Phase 11
Phase 12
Phase 14
Phase 15
```

Phase 13 Demo is strongly recommended for source-code/SaaS commercial marketing but is not necessary for core runtime functionality.

Phase 6 Financial is not required for the initial MVP.

Optional Phase 5 professional extensions may be deferred.

---

# 23. Recommended Milestones

## Milestone A — Technical Foundation

Completed after Phase 2.

Outcome:

```text
Verified Laravel application
+ Authentication
+ Organizations
+ Roles
+ Permissions
```

## Milestone B — Event Configuration Alpha

Completed after Phase 3.

Outcome:

```text
Authorized users can create and configure Draft events.
```

## Milestone C — Functional MVP Alpha

Completed after Phase 4.

Outcome:

```text
Create
→ Publish
→ Register
→ Ticket
→ Check-In
```

This is the first true product milestone.

## Milestone D — Operational MVP Beta

Completed after:

- Phase 5 required scope,
- Phase 7,
- Phase 8,
- Phase 9.

Outcome:

```text
Operationally usable event-management product.
```

## Milestone E — Release Candidate

Completed after Phase 11.

Outcome:

```text
Feature-complete + security-reviewed + regression-tested.
```

## Milestone F — Commercial Build

Completed after Phase 15.

Outcome:

```text
Installable
Documented
Demo-ready where selected
Versioned
Commercially distributable
```

---

# 24. Recommended Development Order Inside the Core MVP

For implementation execution, the most important sequence is:

```text
1. Project bootstrap

2. Authentication

3. Organization
   → Membership
   → Roles
   → Policies

4. Event
   → Venue
   → Agenda
   → Ticket Type
   → Registration Field

5. Publish Event

6. Public Event Page

7. Public Registration
   → Capacity transaction
   → Registration answers
   → Ticket creation

8. Attendee Management

9. Check-In
   → Manual
   → QR
   → Duplicate guard

10. Event Lifecycle Completion / Archive

11. Dashboard / Reports / Export

12. Notifications

13. Settings

14. Security + QA

15. Installer + Docs + Release
```

Do not begin with:

```text
AI
Payment
Mobile App
Vendor Marketplace
Complex SaaS Billing
```

before the core event flow is proven.

---

# 25. Critical Risk Gates

## Risk Gate 1 — Organization Isolation

Must pass during Phase 2.

Failure means:

> Do not proceed to production event data.

## Risk Gate 2 — Capacity Concurrency

Must pass during Phase 4.

Failure means:

> Registration is not production-ready.

## Risk Gate 3 — Duplicate Check-In

Must pass during Phase 4.

Failure means:

> Event-day operations are not production-ready.

## Risk Gate 4 — Reporting Reconciliation

Must pass during Phase 7.

Failure means:

> Management metrics cannot be trusted.

## Risk Gate 5 — Security

Must pass during Phase 10.

Failure means:

> Do not package a release candidate.

## Risk Gate 6 — Restore

Must pass before Phase 15 completion.

Failure means:

> Product is not operationally ready for commercial production use.

---

# 26. Definition of Phase Complete

Every phase is complete only when:

- objectives are achieved,
- planned release-scope modules are implemented,
- dependencies remain stable,
- deliverables exist,
- acceptance criteria pass,
- tests pass,
- critical bugs are resolved,
- authorization is verified,
- organization isolation is preserved,
- documentation impacted by changes is updated,
- no unrelated architecture was introduced,
- no production migration history was modified,
- code follows `CURSOR.md`.

---

# 27. Definition of MVP Complete

EventFlow MVP is complete when a supported installation can demonstrate:

```text
Owner/Admin
    ↓
Organization
    ↓
Team Member Access
    ↓
Create Event
    ↓
Configure Venue / Agenda / Ticket Types
    ↓
Publish
    ↓
Public Attendee Registration
    ↓
Unique Ticket + QR
    ↓
Attendee Management
    ↓
Manual / QR Check-In
    ↓
Attendance
    ↓
Dashboard / Reports
    ↓
CSV Export
    ↓
Complete / Archive Event
```

while satisfying:

- role authorization,
- organization isolation,
- transaction-safe capacity,
- duplicate check-in prevention,
- secure public ticket access,
- notification failure isolation,
- audit requirements,
- responsive attendee/check-in UX,
- backup/restore,
- installation documentation,
- commercial release quality.

---

# 28. Post-MVP Roadmap Direction

After the first commercial release, recommended prioritization is based on customer demand.

## Track A — Professional Event Features

- Event Clone
- Promo Codes
- Waiting List
- Speakers
- Certificates
- Surveys
- Custom Email Templates
- Advanced Reports

## Track B — Commerce

Only after validated demand:

- Payments
- Orders
- Invoices
- Refunds
- Financial Reports

## Track C — Integrations

- REST API
- Webhooks
- Calendar integration
- Messaging integration

## Track D — SaaS

- Plans
- Subscriptions
- Usage Limits
- SaaS Administration
- Trials
- Tenant Billing

## Track E — White Label

- Advanced branding
- Custom domain
- Email branding
- Themes

The project must continue to avoid premature abstraction. Only generalize modules after repeated product use proves they are genuinely reusable.

---

# Final Roadmap Summary

The development strategy for EventFlow is:

```text
FOUNDATION
    ↓
IDENTITY
    ↓
ORGANIZATION SECURITY
    ↓
EVENT MASTER DATA
    ↓
CORE TRANSACTION
    ↓
OPERATIONS
    ↓
REPORTING
    ↓
COMMUNICATION
    ↓
CONFIGURATION
    ↓
SECURITY
    ↓
QUALITY
    ↓
INSTALLABILITY
    ↓
DEMO
    ↓
DOCUMENTATION
    ↓
COMMERCIAL RELEASE
```

The roadmap intentionally keeps the first commercial release centered on:

> **Event Operations + Registration + Ticketing + Check-In + Reporting**

rather than expanding prematurely into payment, marketplace, CRM, native mobile, or enterprise infrastructure.

---

**End of Document**
