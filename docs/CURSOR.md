# CURSOR.md — EventFlow Management System

> Permanent engineering instructions for AI coding assistants working on this repository.

**Project:** EventFlow Management System  
**Target Stack:** Laravel 13.x · PHP 8.4.x · MySQL 8.4.x · Blade · Livewire · Alpine.js · Tailwind CSS  
**Architecture:** Laravel Modular Monolith  
**Deployment Target:** VPS  
**Primary Product Documents:**  
- `docs/PRD.md`
- `docs/SRS.md`
- `docs/SYSTEM_DESIGN.md`
- `docs/BUSINESS_FLOW.md`
- `docs/DATABASE.md`
- `docs/UI_UX.md`

---

# 1. Purpose of This File

This file defines permanent development rules for Cursor and other AI coding assistants working on EventFlow Management System.

These rules apply to:

- new features,
- bug fixes,
- refactoring,
- database changes,
- frontend changes,
- tests,
- documentation,
- dependency changes,
- production fixes.

The AI must follow this file before generating or modifying project code.

If an instruction in this file conflicts with an explicit user instruction for a specific task, the explicit user instruction takes priority **only for that task**, provided it does not violate security, data integrity, or authoritative product requirements.

---

# 2. Source of Truth Hierarchy

Before making product or architecture decisions, use the following authority order.

## 2.1 Product Behavior

`docs/PRD.md`

Defines:

- product scope,
- product behavior,
- roles,
- permissions,
- MVP boundaries,
- acceptance criteria.

## 2.2 Technical Requirements

`docs/SRS.md`

Defines:

- technical constraints,
- Laravel strategy,
- security requirements,
- performance requirements,
- deployment expectations.

## 2.3 Architecture

`docs/SYSTEM_DESIGN.md`

Defines:

- modular monolith architecture,
- domain boundaries,
- application flow,
- queue/cache/deployment strategy,
- architecture decisions.

## 2.4 Business Workflow

`docs/BUSINESS_FLOW.md`

Defines:

- lifecycle flows,
- valid status transitions,
- triggers,
- alternative/error flows,
- audit behavior.

## 2.5 Database

`docs/DATABASE.md`

Defines:

- logical schema,
- entities,
- relationships,
- constraints,
- transaction boundaries,
- indexing strategy.

## 2.6 UI/UX

`docs/UI_UX.md`

Defines:

- navigation,
- page hierarchy,
- form/table patterns,
- public pages,
- check-in UX,
- responsive behavior,
- accessibility.

## 2.7 Existing Code

Existing working code is authoritative for implementation patterns **unless it conflicts with the documents above**.

Never silently resolve a conflict by inventing new product behavior.

When documents and implementation differ:

1. identify the difference,
2. preserve data safety,
3. follow the explicitly authoritative source,
4. keep the implementation change scoped,
5. update documentation if the architecture intentionally changes.

---

# 3. AI Pre-Flight Protocol — Mandatory

Before changing code, the AI must inspect the existing repository.

Do not immediately create files because a common Laravel pattern is expected.

For every implementation task:

## Step 1 — Understand the Requested Change

Identify:

- affected business workflow,
- affected domain/module,
- affected roles,
- affected database state,
- whether the change is MVP or future scope,
- relevant acceptance criteria.

## Step 2 — Read Relevant Documentation

At minimum, inspect the relevant sections of:

- `CURSOR.md`
- `docs/PRD.md`
- `docs/SRS.md`

Then inspect additional documents when applicable:

- business behavior → `docs/BUSINESS_FLOW.md`
- architecture → `docs/SYSTEM_DESIGN.md`
- schema → `docs/DATABASE.md`
- frontend/UX → `docs/UI_UX.md`

Do not read unrelated documents repeatedly if the task is already clearly scoped.

## Step 3 — Inspect Existing Project Configuration

Before assuming framework/tool versions, inspect actual files such as:

```text
composer.json
composer.lock
package.json
package-lock.json / pnpm-lock.yaml / yarn.lock
artisan
vite.config.*
phpunit.xml
.env.example
config/*
```

Target versions in this document are project intentions.

**Actual installed versions come from project configuration.**

Never invent an installed version.

## Step 4 — Inspect Existing Implementation

Before editing, search for:

- relevant routes,
- models,
- policies,
- Form Requests,
- actions/services,
- Livewire components,
- Blade views,
- migrations,
- factories,
- seeders,
- tests,
- existing components/utilities.

Find a similar existing feature and follow its established pattern where appropriate.

## Step 5 — Identify the Smallest Valid Change

Prefer:

```text
small focused diff
```

over:

```text
large architecture rewrite
```

Do not refactor unrelated code while implementing a small feature.

## Step 6 — Check Data and Authorization Impact

Ask internally:

- Does this change cross organization boundaries?
- Does it mutate multiple records?
- Does it need a transaction?
- Does it need a Policy?
- Does it need an audit event?
- Does it need queue-after-commit?
- Does it affect existing statuses?
- Does it affect a migration?
- Could concurrent requests break it?

## Step 7 — Implement

Follow existing architecture and the rules below.

## Step 8 — Verify

Run or create appropriate tests.

Check:

- happy path,
- validation,
- authorization,
- organization isolation,
- failure path,
- state transitions,
- regression risk.

## Step 9 — Review the Diff

Before considering work complete:

- remove accidental changes,
- remove dead code,
- verify no credentials/secrets were added,
- verify no unrelated formatting churn,
- verify documentation changes where needed.

---

# 4. Core Architecture Rules

## 4.1 Architecture Style

EventFlow is a **Laravel modular monolith**.

Do not introduce microservices unless a future architecture decision explicitly requires them.

Do not introduce:

- Kubernetes,
- service mesh,
- distributed transactions,
- event sourcing,
- CQRS infrastructure,
- separate deployable services,

for ordinary MVP features.

## 4.2 Primary Architectural Flow

Use this conceptual direction:

```text
Blade / Livewire
        ↓
Validation
        ↓
Policies / Authorization
        ↓
Application Action / Service
        ↓
Eloquent Models / Queries
        ↓
MySQL
```

Secondary effects occur after valid business state changes:

```text
Business Action
    ├── Audit
    ├── Event
    ├── Queue Job
    ├── Notification
    └── Cache Invalidation
```

## 4.3 Domain Boundaries

Keep these conceptual domains clear:

### Core Platform

- Identity
- Organizations
- Memberships / Roles
- Settings
- Files
- Notifications
- Activity Logs

### Event Domain

- Events
- Venues
- Agenda
- Registration
- Registration Fields
- Attendees
- Ticket Types
- Tickets
- Check-In
- Reporting

Do not create circular dependencies between modules.

## 4.4 MySQL Is Authoritative

MySQL is the authoritative source for transactional business state.

Do not make the following authoritative:

- cache,
- session state,
- email delivery state,
- exported CSV,
- JavaScript state,
- queue payload.

---

# 5. Laravel Conventions

Follow Laravel conventions before inventing custom abstractions.

Prefer framework concepts such as:

- Models
- Relationships
- Query Scopes
- Form Requests
- Policies
- Gates
- Actions / Services
- Jobs
- Events
- Listeners
- Notifications
- Mail
- Filesystem
- Cache
- Queue
- Scheduler
- Validation
- Route Model Binding
- Factories
- Seeders
- Feature Tests

Do not create a custom framework inside Laravel.

---

# 6. PHP Standards

## 6.1 PHP Version

Target PHP is 8.4.x.

Actual project PHP requirement must be read from `composer.json`.

Do not use syntax unsupported by the actual configured PHP version.

## 6.2 Coding Style

Follow:

- PSR-12-compatible style,
- Laravel project formatting conventions,
- existing repository formatter configuration.

## 6.3 Strictness

Use clear types where they improve correctness.

Prefer:

- typed method parameters,
- return types,
- typed properties,
- backed enums/value objects when justified.

Avoid unnecessary type ceremony around straightforward Eloquent usage.

## 6.4 Nullability

Represent real nullable domain state explicitly.

Do not use empty string as a replacement for NULL unless the domain explicitly requires it.

## 6.5 Booleans

Use actual boolean semantics.

Avoid magic integers such as:

```php
$status = 1;
```

when a named state or boolean is required.

## 6.6 Comments

Do not comment obvious code.

Use comments for:

- non-obvious business constraints,
- concurrency reasoning,
- external system quirks,
- temporary compatibility workarounds.

Explain **why**, not what the syntax already says.

---

# 7. Naming Conventions

Use Laravel-standard naming.

## 7.1 Classes

PascalCase:

```text
Event
Registration
TicketType
CheckIn
PublishEvent
RegisterAttendee
EventPolicy
SendEventReminder
```

## 7.2 Methods and Variables

camelCase:

```text
registrationCode
remainingCapacity
canPublish()
checkInAttendee()
```

## 7.3 Database

snake_case:

```text
organization_id
registered_at
ticket_type_id
checked_in_at
```

## 7.4 Routes

Use readable kebab-case where explicit paths are needed:

```text
/events
/events/{event}
/forgot-password
```

Named routes should follow consistent dot notation:

```text
events.index
events.show
events.edit
events.attendees.index
events.check-in.index
```

## 7.5 Action Classes

Use verb-focused names:

```text
CreateEvent
PublishEvent
CancelEvent
RegisterAttendee
IssueTicket
CheckInAttendee
ChangeMemberRole
```

Avoid vague classes such as:

```text
EventManager
HelperService
CommonService
Utility
ProcessData
```

unless responsibility is genuinely specific.

---

# 8. Models

## 8.1 Model Responsibilities

Models may contain:

- relationships,
- casts,
- local scopes,
- accessors/mutators where justified,
- small state helpers,
- simple model-specific invariants.

Models should not become giant service objects.

## 8.2 Relationships

Define explicit Eloquent relationships.

Use relationship methods instead of ad-hoc joins when normal Eloquent relationships fit.

## 8.3 Eager Loading

Prevent N+1 queries.

Use eager loading intentionally:

```text
with()
load()
loadMissing()
```

Do not eagerly load large relationship graphs by default.

Load only what the current use case needs.

## 8.4 Global Scopes

Do not introduce broad organization global scopes casually.

Organization isolation is critical, but implicit global scopes can make administrative/reporting behavior difficult to reason about.

Prefer explicit organization-scoped queries and authorization unless the existing architecture has a proven tenant-scope implementation.

## 8.5 Mass Assignment

Explicitly control writable fields using the project's chosen Laravel model strategy.

Never persist unvalidated request payloads blindly.

## 8.6 Model Events

Do not hide major business workflows in model observers/events.

Do not implement registration, ticket issuance, check-in, or event publication solely through `creating`, `created`, `updating`, or `updated` hooks.

Critical workflows belong in explicit actions/services.

---

# 9. Controllers

## 9.1 Keep Controllers Thin

Controllers are transport/orchestration layers.

A controller may:

- receive request,
- call authorization,
- invoke Form Request validation,
- call an Action/Service,
- return response/view/redirect.

A controller must not contain large business workflows.

## 9.2 Prohibited Controller Logic

Do not put directly in controllers:

- capacity calculation,
- multi-model state transitions,
- ticket issuance workflow,
- organization ownership validation,
- check-in duplicate logic,
- notification orchestration,
- report aggregation logic.

## 9.3 Resource Controllers

Use resource-style controllers when they match the feature.

Do not force every action into CRUD terminology.

Domain actions such as:

```text
publish
cancel
archive
check-in
```

may use dedicated methods/routes/actions.

---

# 10. Application Actions and Services

## 10.1 Use Actions for Business Use Cases

Use dedicated Action/Service classes when a workflow:

- spans multiple models,
- needs a transaction,
- contains important business rules,
- may be called from multiple entry points,
- dispatches secondary work,
- needs focused tests.

Examples:

```text
PublishEvent
RegisterAttendee
CheckInAttendee
CancelRegistration
ChangeMemberRole
```

## 10.2 Services Must Be Specific

Prefer:

```text
RegistrationCapacityService
AttendanceReportQuery
```

over:

```text
EventService
GeneralService
CommonService
```

unless the responsibility truly belongs to one coherent service.

## 10.3 No Service Layer Ceremony for Simple CRUD

Do not create an Action + Interface + Repository + Manager + DTO for a trivial single-model update.

Use the simplest design that respects business rules.

---

# 11. Repository Strategy

## 11.1 Default Rule

Do **not** create repository interfaces for every Eloquent model.

Eloquent is the default persistence abstraction.

## 11.2 Repositories Are Justified Only When

- multiple persistence systems exist,
- external storage replaces local persistence,
- a complex reusable data gateway is required,
- persistence swapping is an actual requirement.

## 11.3 Complex Read Logic

Prefer:

- query scopes,
- dedicated query classes,
- report query objects,

for complex reads.

---

# 12. Form Requests and Validation

## 12.1 Server-Side Validation Is Mandatory

Never trust:

- browser validation,
- Alpine.js validation,
- Livewire frontend state,
- hidden fields,
- select options sent by clients.

All state-changing requests require server validation.

## 12.2 Form Requests

Use Form Requests for complex HTTP validation.

Examples:

- creating/updating event,
- organization settings,
- member invitation,
- ticket type configuration.

## 12.3 Livewire Validation

Livewire components may use Livewire validation for component forms.

For shared complex rules, extract reusable validation Rules or domain validation rather than duplicating rules.

## 12.4 Business Validation Is Different

Form validation answers:

> Is the input structurally valid?

Business validation answers:

> Is this operation valid in the current domain state?

Examples of business validation:

- Can this event be published?
- Is registration open now?
- Is capacity available?
- Can this user remove this Owner?
- Is this registration eligible for check-in?

Do not place these rules only in form rules.

---

# 13. Authorization

## 13.1 Policies/Gates Are Mandatory

Use Laravel Policies and Gates for protected business resources.

Never rely on:

- hidden menu,
- disabled button,
- frontend role check,
- guessed route secrecy.

## 13.2 Authorization Dimensions

Every protected action may require:

1. authenticated user,
2. active organization membership,
3. membership role,
4. event assignment,
5. requested resource organization,
6. action capability.

## 13.3 Organization Isolation

Organization isolation is a security boundary.

A user from Organization A must never retrieve private data from Organization B by manipulating:

- IDs,
- route parameters,
- filters,
- search strings,
- export parameters,
- Livewire payloads.

## 13.4 Reports and Exports

Authorization rules apply equally to:

- UI pages,
- JSON responses,
- reports,
- CSV exports,
- file downloads.

## 13.5 Role Model

MVP roles:

```text
owner
admin
event_manager
staff
viewer
```

Do not introduce a dynamic permission-management package unless product requirements explicitly change.

---

# 14. Organization and Event Assignment Rules

## 14.1 Membership

`organization_memberships` determines:

- organization access,
- role.

## 14.2 Event Assignment

`event_assignments` scopes:

- Event Manager
- Staff
- Viewer

to assigned events when assignment rules apply.

Owner/Admin generally do not require individual event assignment.

## 14.3 Last Owner Invariant

Never allow an active organization to have zero active Owners.

Role removal/change must be transaction-safe.

---

# 15. Database Migrations

## 15.1 Never Modify Existing Production Migrations

Once a migration has been used in a production/released environment:

**do not edit it.**

Create a new migration for schema changes.

## 15.2 New Schema Change Rule

Every schema change must be represented by a new migration.

Examples:

```text
add_x_to_events_table
create_event_assignments_table
add_index_to_registrations_table
```

## 15.3 Migration Safety

Before adding:

- NOT NULL column,
- unique index,
- foreign key,
- changed data type,

consider existing production data.

Backfill safely before enforcing stronger constraints.

## 15.4 Foreign Keys

Use FK constraints for stable business relationships.

Avoid broad cascade deletes for event history.

## 15.5 Indexes

Add indexes based on actual query patterns and `docs/DATABASE.md`.

Do not index every column automatically.

## 15.6 Schema Naming

Follow `docs/DATABASE.md` unless the schema has intentionally evolved and documentation is updated.

---

# 16. Database Transactions

Use database transactions for multi-step critical operations.

Mandatory examples:

- registration creation + capacity validation + ticket issuance,
- registration cancellation,
- check-in,
- last Owner role/removal changes,
- event lifecycle transitions when audit/state must remain consistent.

## 16.1 Transactions Must Be Short

Do not perform slow external calls inside a DB transaction.

Never send email inside the registration transaction.

Pattern:

```text
BEGIN
→ validate current locked state
→ write authoritative records
→ write critical audit
COMMIT
→ dispatch queue/notification
```

## 16.2 Capacity Concurrency

Never implement ticket capacity as:

```text
if count < capacity
    insert
```

without concurrency protection.

The registration workflow must use the transaction/locking strategy defined in `docs/DATABASE.md`.

## 16.3 Check-In Concurrency

`check_ins.registration_id` uniqueness is a final database guard.

Application code must convert a race-condition uniqueness conflict into the correct business result:

```text
Already checked in
```

not a generic 500 error.

---

# 17. Status and Lifecycle Rules

Do not silently add or change statuses.

## 17.1 Event Statuses

Current event states:

```text
draft
published
ongoing
completed
cancelled
archived
```

Valid transitions are defined in `docs/BUSINESS_FLOW.md`.

Do not invent transitions such as:

```text
cancelled → published
archived → ongoing
completed → draft
```

unless product requirements change.

## 17.2 Registration Status

Current derived MVP proposal:

```text
confirmed
cancelled
```

This status vocabulary was derived because the PRD did not enumerate it fully.

Do not add:

```text
pending
paid
approved
rejected
```

without explicit product requirement.

## 17.3 Check-In State

Do not create a duplicated `checked_in` boolean if check-in is already represented by `check_ins`.

Check-in state is derived from the attendance record.

---

# 18. Jobs

## 18.1 Use Jobs for Slow or Asynchronous Work

Examples:

- email delivery,
- reminders,
- large export generation if needed later,
- long-running image processing if later introduced.

## 18.2 Jobs Must Be Retry-Safe

A job may run more than once.

Design jobs to be:

- idempotent, or
- protected against duplicated side effects.

## 18.3 Queue After Commit

If a job depends on newly committed records, dispatch it after commit.

Do not allow a worker to run against uncommitted registration/ticket state.

## 18.4 Keep Business Decisions Out of Jobs

The primary business decision should happen before dispatch whenever possible.

Example:

Bad:

```text
queue a job that later decides if registration should exist
```

Preferred:

```text
commit registration
→ queue confirmation email
```

---

# 19. Events and Listeners

Use Laravel events/listeners for secondary side effects or decoupling where useful.

Appropriate:

```text
RegistrationConfirmed
TicketIssued
AttendeeCheckedIn
```

Potential listeners:

- queue notification,
- invalidate aggregate cache,
- perform secondary analytics/logging.

Do not hide the primary business transaction behind a chain of listeners.

The code path for a critical operation must remain understandable.

---

# 20. Notifications and Email

## 20.1 Prefer Laravel Native Notifications/Mail

Do not hard-code a specific provider in business logic.

## 20.2 Required MVP Notification Types

Potential current workflows:

- password reset,
- organization invitation,
- registration confirmation,
- ticket access,
- event reminder if enabled.

## 20.3 Delivery Failure

Email failure must never roll back:

- successful registration,
- valid event status transition,
- ticket creation,
- check-in.

## 20.4 Sensitive Content

Do not email:

- internal admin notes,
- another attendee's ticket,
- application secrets.

---

# 21. Cache

## 21.1 Cache Is Never Authoritative

Do not use cache as the source for:

- authorization,
- ticket validity,
- current check-in state,
- registration eligibility,
- remaining capacity.

## 21.2 Appropriate Cache Usage

Cache may optimize:

- dashboard aggregates,
- public event reads,
- configuration/reference values.

## 21.3 Invalidation

State changes must invalidate relevant organization/event cache or use a deliberately safe TTL.

---

# 22. Livewire Components

## 22.1 Role

Livewire belongs to the presentation/application boundary.

A Livewire component may:

- hold UI state,
- validate form fields,
- authorize an action,
- invoke an application Action/Service,
- render query results.

## 22.2 Do Not Put Large Business Logic in Livewire Methods

Bad:

```text
Livewire method
→ calculate capacity
→ create registration
→ create ticket
→ send email
→ update dashboard
```

Preferred:

```text
Livewire method
→ validate
→ authorize
→ RegisterAttendee action
→ display result
```

## 22.3 Query Efficiency

Livewire re-renders can accidentally multiply queries.

Check query counts and avoid:

- repeated relationship access,
- loading unbounded datasets,
- N+1 in Blade loops.

## 22.4 Pagination

Use pagination for large tables.

Required for lists such as:

- events,
- attendees,
- activity logs,
- reports with detailed rows.

Do not `->get()` 10,000 attendee records for an ordinary table.

---

# 23. Blade

## 23.1 Escape Output

Escape user-generated output by default.

Use normal Blade escaped output.

Do not render raw `{!! !!}` user content unless content has been explicitly sanitized and the requirement is documented.

## 23.2 Components

Use reusable Blade components where UI patterns repeat.

Do not create components for trivial one-off markup merely for abstraction.

## 23.3 Authorization UI

The UI may hide actions users cannot perform.

Server-side policy checks remain mandatory.

---

# 24. Alpine.js

Use Alpine for lightweight browser-only behavior:

- dropdowns,
- toggles,
- small modal state,
- collapsible sections,
- camera/scanner browser coordination.

Do not use Alpine as the authoritative business state store.

Do not duplicate server-side business rules in Alpine.

---

# 25. Tailwind CSS

Follow existing design tokens/patterns from `docs/UI_UX.md`.

Avoid:

- arbitrary colors everywhere,
- page-specific style systems,
- excessive gradients,
- decorative dashboard clutter,
- unnecessary animations.

Maintain a professional commercial SaaS visual system.

Use responsive utilities intentionally.

---

# 26. Routes

## 26.1 Web First

The primary product is Blade/Livewire web application.

Use `web` routes and session authentication for MVP.

## 26.2 Public Routes

Public event routes may include:

```text
/e/{public_slug}
registration
secure ticket access
```

Public routes must expose only public data.

## 26.3 Authenticated Routes

Organization/event routes must:

- authenticate,
- resolve scope,
- authorize.

## 26.4 Route Naming

Use consistent named routes.

Do not scatter hard-coded URLs through Blade templates.

---

# 27. API

## 27.1 No Public API Required in MVP

Do not build API-first architecture unless the product requirement changes.

The Blade/Livewire frontend does not need a separate REST API merely to call the same Laravel application.

## 27.2 If an API Is Added Later

Requirements:

- versioned routes,
- explicit authentication,
- authorization,
- organization scoping,
- rate limiting,
- documentation,
- backward compatibility.

Laravel Sanctum may be considered if justified by the verified use case.

Do not install it solely because Laravel APIs often use it.

---

# 28. Webhooks

Webhooks are not an MVP requirement.

Do not create webhook delivery infrastructure unless a real integration needs it.

Future webhooks must address:

- signatures,
- retry,
- idempotency,
- timeout,
- delivery logs,
- versioned payloads.

---

# 29. File Uploads

## 29.1 Validate Every Upload

Validate:

- MIME type,
- extension where relevant,
- file size,
- user authorization,
- ownership scope.

Never trust the original filename or browser MIME declaration alone.

## 29.2 Storage

Use Laravel Filesystem.

Do not store uploaded image binary data as MySQL BLOBs for current MVP.

## 29.3 Paths

Generate safe application-controlled storage paths.

Do not use unsanitized user filenames as final storage paths.

## 29.4 Private Files

Private files require:

- authorized delivery, or
- signed access.

Do not expose predictable private storage paths.

---

# 30. Search

Use MySQL-backed search for MVP.

Do not add:

- Elasticsearch,
- OpenSearch,
- Meilisearch,

without measured need.

Search queries must always apply organization/event authorization scope.

Optimize with:

- appropriate indexes,
- pagination,
- targeted queries.

---

# 31. Reporting and Export

## 31.1 Reporting

Use authoritative MySQL data.

Do not create a separate analytics database for MVP.

Dashboard/report definitions must remain consistent.

## 31.2 Exports

Use CSV when it satisfies product requirements.

Do not install a spreadsheet package just to export CSV.

For large exports:

- stream/chunk,
- avoid loading all rows into memory.

## 31.3 Export Authorization

Export is a data-access operation.

Apply the same Policy/scope rules as the UI.

---

# 32. Security

Security rules are mandatory.

## 32.1 Never Commit Secrets

Never commit:

- `.env`
- API keys
- SMTP credentials
- DB credentials
- cloud credentials
- private keys
- tokens
- passwords.

## 32.2 Never Expose Configuration

Do not return or log:

- `APP_KEY`
- DB password
- SMTP password
- full environment config.

## 32.3 CSRF

State-changing browser requests must use Laravel CSRF protection.

## 32.4 XSS

Escape user-generated output.

Raw HTML rendering requires explicit sanitization.

## 32.5 SQL Injection

Use:

- Eloquent,
- Query Builder,
- bound parameters.

Never concatenate untrusted input into raw SQL.

## 32.6 Authentication

Use Laravel's configured secure password hashing.

Do not implement custom password hashing.

## 32.7 Ticket Security

Ticket/QR references must be:

- unique,
- unpredictable,
- non-sequential where public.

Never expose raw DB IDs as the only ticket authorization secret.

## 32.8 Rate Limiting

Apply or consider rate limiting to:

- login,
- forgot password,
- public registration,
- high-risk public lookup,
- future API.

## 32.9 Production Debug

Never enable verbose debug output in production.

---

# 33. Error Handling

## 33.1 Expected Business Errors

Expected conflicts are not generic server failures.

Examples:

- event not publish-ready,
- registration closed,
- sold out,
- invalid ticket,
- duplicate check-in,
- last Owner removal attempt.

Return controlled business feedback.

## 33.2 Unexpected Errors

Unexpected exceptions should:

- be logged,
- return safe user-facing errors,
- avoid exposing internals.

## 33.3 Database Constraint Errors

Where a DB constraint protects a known business invariant, convert predictable violations into meaningful business responses.

Example:

```text
check-in unique violation
→ Already checked in
```

Do not show raw SQL constraint messages.

---

# 34. Logging

## 34.1 Technical Logs

Use Laravel logging for:

- exceptions,
- queue failures,
- mail failures,
- scheduler failures,
- integration failures.

## 34.2 Context

Add safe context where useful:

- user ID,
- organization ID,
- event ID,
- job/action name.

## 34.3 Never Log Secrets

Do not log:

- passwords,
- session cookies,
- reset tokens,
- full QR private token,
- credentials.

---

# 35. Activity Logging

Business audit logs are not the same as technical logs.

Required business events include those defined in PRD/Business Flow.

Examples:

```text
event.created
event.updated
event.published
event.cancelled
event.archived
organization.settings_changed
organization.member.role_changed
organization.member.removed
registration.status_changed
checkin.manual
checkin.qr
```

Audit rows must not be editable through ordinary product UI.

---

# 36. Testing

Important business logic must have tests.

Do not consider a feature complete solely because it renders successfully.

## 36.1 Required Test Categories

Use:

- unit tests for isolated business logic,
- feature tests for HTTP/Livewire/domain workflows,
- integration tests for DB/queue/mail/filesystem,
- browser/E2E tests where high-value and available.

## 36.2 Critical Tests

Always prioritize tests for:

### Authentication

- login,
- unauthorized access,
- password reset where changed.

### Organization Isolation

Test that Organization A cannot:

- view,
- edit,
- export,
- search

Organization B data.

### Authorization Matrix

Test relevant roles:

- Owner
- Admin
- Event Manager
- Staff
- Viewer

### Event Lifecycle

Test valid and invalid transitions.

### Registration

Test:

- registration open,
- closed,
- sold out,
- invalid fields,
- ticket issuance.

### Concurrency

Critical tests:

- two requests for last ticket capacity,
- duplicate registration submit where prevention applies,
- two simultaneous check-ins.

### Check-In

Test:

- valid QR,
- manual check-in,
- duplicate,
- invalid QR,
- wrong event,
- cancelled registration.

### Reporting

Test dashboard/report reconciliation.

### File Upload

Test:

- valid,
- invalid MIME,
- oversized,
- unauthorized.

## 36.3 Test Data

Use factories.

Do not depend on manual database state for automated tests.

## 36.4 Tests Must Not Call Real External Services

Use Laravel fakes/mocks for:

- mail,
- notifications,
- queue,
- filesystem,
- external HTTP.

---

# 37. Performance

## 37.1 Prevent N+1

N+1 queries are not acceptable.

Inspect relational loops.

Use eager loading only where needed.

## 37.2 Pagination

Use pagination for potentially large datasets.

Especially:

- attendees,
- events,
- activity logs,
- detailed reports.

## 37.3 Query in Database

Do not fetch thousands of rows to PHP just to:

- count,
- filter,
- group,
- aggregate.

Use SQL/Eloquent aggregate queries.

## 37.4 Cache Only When Useful

Do not add caching to hide an inefficient query before optimizing the query.

## 37.5 Performance Targets

Respect targets defined in `docs/SRS.md`.

---

# 38. Dependency and Package Rules

## 38.1 Prefer Laravel-Native Functionality

Before installing a package, check whether Laravel already provides the needed capability.

Examples:

- authorization → Policies/Gates
- queue → Laravel Queue
- filesystem → Laravel Filesystem
- notifications → Laravel Notifications
- mail → Laravel Mail
- validation → Laravel Validator/Form Requests
- scheduler → Laravel Scheduler

## 38.2 Package Introduction Requires Justification

Before adding a package, the AI must determine:

1. What exact problem does it solve?
2. Why Laravel-native code is insufficient?
3. Is the package actively maintained?
4. Is it compatible with actual Laravel/PHP versions?
5. Does it create vendor lock-in?
6. What is the removal/upgrade risk?
7. Is it worth the maintenance cost?

## 38.3 Do Not Install Packages Speculatively

Examples:

Do not automatically install:

- permission package,
- repository package,
- DTO framework,
- API framework,
- admin panel framework,
- search server client,
- spreadsheet package,
- media library,

unless the feature actually needs it.

---

# 39. Documentation

Update documentation when behavior or architecture changes.

## 39.1 Product Rule Change

If product behavior changes, update:

- `docs/PRD.md`
- `docs/BUSINESS_FLOW.md`

as appropriate.

## 39.2 Technical Architecture Change

Update:

- `docs/SRS.md`
- `docs/SYSTEM_DESIGN.md`

## 39.3 Database Change

Update:

- `docs/DATABASE.md`

when schema architecture changes materially.

## 39.4 UI Pattern Change

Update:

- `docs/UI_UX.md`

for major information architecture/design changes.

## 39.5 CURSOR.md

Update this file only when permanent engineering rules change.

Do not edit documentation just to reflect trivial implementation details.

---

# 40. Git Discipline

## 40.1 Focused Changes

One feature/fix should produce a focused set of changes.

Do not include unrelated refactoring.

## 40.2 Do Not Reformat Unrelated Files

Avoid broad formatting churn.

## 40.3 Do Not Commit Generated/Secret Files Improperly

Follow `.gitignore`.

Never commit:

- `.env`
- runtime logs
- cache files
- local uploads
- credentials
- editor-specific secrets.

## 40.4 Migrations

A schema change and its migration belong together.

Never rewrite production migration history.

## 40.5 Commit Intent

When proposing commit messages, prefer clear imperative descriptions:

```text
Add event publication workflow
Prevent duplicate attendee check-ins
Add organization-scoped attendee search
```

Avoid:

```text
updates
fix stuff
changes
```

---

# 41. Backward Compatibility

Maintain backward compatibility whenever possible.

Before changing:

- route names,
- database columns,
- public URLs,
- enum/status values,
- event payloads,
- configuration keys,

consider existing installations.

For source-code customers, breaking changes create upgrade cost.

If a breaking change is required:

1. justify it,
2. create migration path,
3. document it,
4. provide compatibility where reasonable.

---

# 42. Refactoring Rules

## 42.1 Small Feature ≠ Large Refactor

Never perform a large unrelated refactor while implementing a small feature.

If existing code is imperfect but functional:

- make the minimal safe improvement needed,
- note larger cleanup separately.

## 42.2 Refactor When

- duplication directly affects current feature,
- current design blocks safe implementation,
- tests protect behavior,
- scope is controlled.

## 42.3 Do Not Rewrite Architecture for Style Preference

Do not change:

```text
Eloquent → Repository abstraction
Blade/Livewire → SPA
Monolith → Microservices
database queue → Redis
```

merely because another architecture is fashionable.

Architecture decisions are explicit in project documentation.

---

# 43. EventFlow-Specific Invariants

These are non-negotiable until product requirements change.

## 43.1 Organization Boundary

Every protected event resource belongs to an organization.

Cross-organization access is forbidden.

## 43.2 Owner Invariant

An active organization cannot have zero active Owners.

## 43.3 Event Publication

Draft events are not public and do not accept public registration.

Only publish-ready events may become Published.

## 43.4 Event Cancellation

Cancelled events do not accept new registrations.

Do not delete registration history when an event is cancelled.

## 43.5 Registration Capacity

Registration must not exceed:

- event capacity,
- selected ticket type capacity.

Capacity validation must be transaction-safe.

## 43.6 Registration Confirmation

Successful public registration creates authoritative registration state before notification delivery.

## 43.7 Ticket Identity

Each ticket has a unique non-sequential public identity.

## 43.8 Check-In

A registration cannot silently generate multiple successful check-in records.

## 43.9 Reporting

Dashboard/report metrics derive from authoritative transactional data.

## 43.10 Audit

Required administrative actions create business activity records.

---

# 44. MVP Scope Guardrails

Do not add future features while implementing current MVP unless explicitly requested.

Current MVP does not require:

- payment gateway,
- invoice engine,
- refund processing,
- vendor portal,
- sponsor marketplace,
- native Android app,
- native iOS app,
- public REST API,
- webhook platform,
- AI assistant,
- live streaming,
- video conference,
- CRM,
- marketing automation,
- dynamic permission builder,
- database-per-tenant,
- external search engine.

Avoid “future-proofing” by implementing unused infrastructure.

Create extension points only when they make current code clearer or materially reduce future migration risk.

---

# 45. UI/UX Engineering Rules

Follow `docs/UI_UX.md`.

## 45.1 SaaS Visual Direction

The product should feel:

- professional,
- clean,
- restrained,
- operational,
- reliable.

Avoid “AI slop” visual patterns such as:

- unnecessary gradients,
- oversized marketing cards in admin screens,
- excessive glassmorphism,
- random icons,
- meaningless charts,
- decorative animation everywhere.

## 45.2 Page Hierarchy

Respect:

```text
Organization navigation
→ Event navigation
→ Entity/action detail
```

Do not create a global sidebar item for every database table.

## 45.3 Check-In

Check-In is mobile-critical.

Always preserve:

- QR scan,
- manual search fallback,
- success state,
- duplicate state,
- invalid state.

## 45.4 Public Registration

Do not require attendee account creation in MVP.

Keep public registration friction low.

---

# 46. Accessibility

Do not trade accessibility for visual polish.

Mandatory:

- semantic HTML,
- labels for form fields,
- visible focus,
- keyboard navigation,
- accessible validation messages,
- status text not color-only,
- accessible modal focus behavior,
- mobile touch targets.

Camera-based check-in must always have a non-camera fallback.

---

# 47. Environment Configuration

Never hard-code environment-specific configuration.

Use config/environment for:

- DB,
- mail,
- filesystem,
- queue,
- cache,
- sessions,
- app URL,
- locale,
- feature flags where justified.

Update `.env.example` when adding required environment variables.

Do not place actual credentials in `.env.example`.

---

# 48. Deployment-Aware Engineering

Target deployment is conventional VPS.

Avoid introducing infrastructure that makes basic self-hosting unnecessarily difficult.

Initial architecture should remain capable of running with:

- web server,
- PHP-FPM,
- Laravel,
- MySQL,
- persistent filesystem,
- database-backed sessions,
- database queue,
- queue worker,
- scheduler cron,
- SMTP/email provider.

Redis and S3-compatible storage are optional scaling paths, not mandatory MVP dependencies.

---

# 49. Scheduler Rules

Use Laravel Scheduler for recurring application work.

Examples:

- event reminders,
- maintenance cleanup.

Scheduled commands/jobs must be idempotent where they may run repeatedly.

Do not place long-running external work directly in the scheduler when a queued job is more appropriate.

---

# 50. Error Message Rules

User-facing messages should describe business outcomes.

Good:

```text
Registration is closed.
This ticket has already been checked in.
Every organization must have at least one Owner.
```

Bad:

```text
Integrity constraint violation.
SQLSTATE[23000].
Unauthorized model ID.
```

Do not expose implementation details.

---

# 51. Data Deletion Rules

Do not hard-delete business history casually.

Use domain behavior:

- Event → archive/cancel
- Registration → cancel
- Ticket Type → deactivate
- Registration Field → deactivate
- Membership → removed_at

Hard deletion and privacy anonymization require explicit requirements.

---

# 52. Demo Mode

If demo mode is implemented later:

- use synthetic data,
- display demo status clearly,
- restrict dangerous system actions,
- do not store customer secrets,
- allow predictable reset behavior.

Do not add demo-mode conditionals across business logic without a centralized strategy.

---

# 53. Code Review Checklist for AI

Before finishing any coding task, verify:

## Architecture

- [ ] Follows modular monolith.
- [ ] Follows existing project pattern.
- [ ] No unnecessary package/abstraction added.
- [ ] No unrelated refactor.

## Data

- [ ] Organization scope is enforced.
- [ ] FK/constraint expectations are respected.
- [ ] Transactions used when required.
- [ ] No duplicated derived state introduced.
- [ ] Existing production migrations were not edited.

## Laravel

- [ ] Controller/Livewire component remains thin.
- [ ] Business logic is in appropriate Action/Service.
- [ ] Complex validation uses Form Request/reusable rules.
- [ ] Authorization uses Policy/Gate.
- [ ] N+1 risk checked.
- [ ] Pagination used for large lists.

## Security

- [ ] Client input is server validated.
- [ ] Output is escaped.
- [ ] Uploads are validated.
- [ ] No secrets exposed or committed.
- [ ] Public identifiers are safe.
- [ ] Authorization is server-side.

## Async

- [ ] Jobs are retry-safe.
- [ ] Transaction-dependent jobs dispatch after commit.
- [ ] Email failure cannot corrupt committed business state.

## Tests

- [ ] Important happy path tested.
- [ ] Failure/validation tested.
- [ ] Authorization tested.
- [ ] Organization isolation tested where relevant.
- [ ] Concurrency tested for critical operations.

## Documentation

- [ ] Product docs updated if behavior changed.
- [ ] Architecture docs updated if architecture changed.
- [ ] Database docs updated if schema changed.

---

# 54. Prohibited AI Behaviors

AI assistants must **not** do the following without explicit justification/instruction.

## Do Not Guess Existing Architecture

Never assume a file/class/package exists.

Search first.

## Do Not Guess Versions

Read project configuration.

## Do Not Rewrite Working Code Unnecessarily

Do not replace a functioning implementation just because another style is preferred.

## Do Not Add Packages Automatically

Justify every dependency.

## Do Not Change Business Rules Silently

Never silently:

- add a status,
- remove a validation,
- change capacity semantics,
- change role access,
- change lifecycle transitions.

## Do Not Modify Production Migration History

Create new migrations.

## Do Not Remove Authorization to “Fix” a Feature

If authorization blocks a workflow, understand the policy and requirements first.

## Do Not Disable Security for Convenience

Never solve local development issues by weakening production security.

## Do Not Introduce Broad `try/catch` That Hides Failures

Catch exceptions only when the application can handle them meaningfully.

## Do Not Use Raw SQL Without Need

Prefer Eloquent/Query Builder and bound parameters.

## Do Not Store Secrets in Logs

Ever.

## Do Not Create Fake Placeholder Features

Do not add navigation/pages for future modules that are not implemented.

---

# 55. AI Change Strategy

When asked to implement a feature, AI should internally use this reasoning order:

```text
1. What product requirement authorizes this feature?
2. What module owns it?
3. What existing code already handles adjacent behavior?
4. What is the smallest safe change?
5. Does it change database state?
6. Does it require transaction/concurrency protection?
7. Who is allowed to do it?
8. What validation is required?
9. What secondary effects occur?
10. What must be tested?
11. Does documentation need updating?
```

Do not start from:

```text
Which package can implement this fastest?
```

Start from the product and existing code.

---

# 56. Definition of Engineering Done

A development task is done when:

1. behavior matches the authoritative product requirement,
2. code follows existing architecture,
3. validation is server-side,
4. authorization is enforced,
5. transactions protect critical operations,
6. data integrity constraints are respected,
7. important paths have tests,
8. error states are user-safe,
9. no N+1 or unbounded-list issue was introduced,
10. no credential or secret is exposed,
11. no unrelated refactor is included,
12. documentation is updated when needed,
13. the diff is understandable to another Laravel engineer,
14. backward compatibility is preserved whenever reasonably possible.

---

# 57. EventFlow Engineering Philosophy

When several solutions are valid, prefer the one that is:

1. easiest to understand,
2. easiest to test,
3. safest for transactional data,
4. closest to Laravel conventions,
5. easiest to deploy on a VPS,
6. easiest for source-code customers to maintain,
7. least dependent on unnecessary third-party packages.

The preferred EventFlow engineering approach is:

```text
Simple
+ Explicit
+ Laravel-native
+ Transaction-safe
+ Organization-scoped
+ Testable
+ Commercially maintainable
```

Not:

```text
Abstract
+ fashionable
+ package-heavy
+ distributed
+ prematurely generalized
```

---

# 58. Final Mandatory Rules

These rules are non-negotiable unless an explicit architecture/product decision changes them.

- Follow Laravel conventions.
- Follow the existing project architecture.
- Analyze existing code before changing files.
- Keep controllers thin.
- Keep Livewire components focused on UI orchestration.
- Do not place major business logic directly in controllers or Livewire handlers.
- Use focused Actions/Services for important multi-step business workflows.
- Use Form Requests for complex HTTP validation.
- Never trust client-side validation.
- Use Policies/Gates for authorization.
- Enforce organization isolation server-side.
- Use database transactions for multi-step critical operations.
- Protect registration capacity against concurrency.
- Protect check-in against duplicate concurrent writes.
- Never modify existing production migrations.
- Create new migrations for schema changes.
- Prevent N+1 queries.
- Use eager loading appropriately.
- Use pagination for large data tables.
- Escape user-generated output.
- Validate uploads.
- Never expose sensitive configuration.
- Never commit credentials.
- Never log secrets.
- Write tests for important business logic.
- Test authorization and organization isolation.
- Never introduce a package without justification.
- Prefer Laravel-native functionality.
- Do not introduce repository-per-model architecture without a real need.
- Do not introduce microservices without a strong validated justification.
- Never perform large unrelated refactoring while implementing a small feature.
- Do not silently change business rules.
- Do not silently add statuses or lifecycle transitions.
- Keep email/external failures outside committed core business state.
- Dispatch transaction-dependent async work after commit.
- Keep cache non-authoritative.
- Preserve historical business data.
- Update documentation when architecture or business behavior changes.
- Maintain backward compatibility whenever possible.
- Keep the application straightforward to deploy on a conventional VPS.
- Optimize for commercial maintainability, not architectural novelty.

---

**End of CURSOR.md**
