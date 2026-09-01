# EventFlow Management System — Software Requirements Specification (SRS)

**Document Path:** `docs/SRS.md`  
**Product:** EventFlow Management System  
**Document Type:** Software Requirements Specification  
**PRD Authority:** `docs/PRD.md` Version 1.0  
**Architecture Style:** Modular Monolith  
**Deployment Target:** VPS  
**Document Version:** 1.0  
**Status:** Draft for Technical Design  

---

## Technical Stack Verification Status

The technical environment available during preparation of this SRS was inspected for common project configuration files including:

- `composer.json`
- `package.json`
- `artisan`
- `.env.example`
- Vite configuration

No existing Laravel application configuration was available in the inspected project area. Therefore, this SRS distinguishes between the **requested target stack** and the **actual installed environment**.

| Component | Requested / Recommended Target | Actual Installed Version |
|---|---|---|
| Backend Framework | Laravel 13.x | **TBD — Requires Environment Verification** |
| PHP | PHP 8.4.x | **TBD — Requires Environment Verification** |
| Database | MySQL 8.4.x LTS | **TBD — Requires Environment Verification** |
| Server Rendering | Blade, version coupled to Laravel | **TBD — Requires Environment Verification** |
| Reactive UI | Livewire 4.x recommended for a new Laravel 13 project | **TBD — Requires Environment Verification** |
| Lightweight JavaScript | Alpine.js compatible with selected Livewire setup | **TBD — Requires Environment Verification** |
| CSS | Tailwind CSS 4.x recommended for a new project | **TBD — Requires Environment Verification** |
| Asset Build | Vite-compatible Laravel frontend pipeline | **TBD — Requires Environment Verification** |
| Web Server | VPS web server; Nginx recommended | **TBD — Requires Environment Verification** |
| Queue Backend | Laravel database queue for MVP; Redis optional later | **TBD — Requires Environment Verification** |
| Cache Backend | File/database for simple single-node MVP; Redis optional later | **TBD — Requires Environment Verification** |
| Session Backend | Database recommended for production | **TBD — Requires Environment Verification** |

### Compatibility Position

For a new project, the requested Laravel 13.x + PHP 8.4.x + MySQL 8.4.x stack is a valid target. Exact patch versions shall be selected and locked when the project is initialized. Frontend package versions shall be verified from the generated `package.json` and lockfile rather than assumed.

### Stack Change Rule

If the actual project environment later differs from this target:

1. Actual configuration files take precedence for installed-version documentation.
2. Unsupported combinations shall be corrected before feature implementation.
3. `docs/SRS.md` shall be updated to reflect verified versions.
4. No dependency shall be upgraded solely to match this document without compatibility review.

---

# 1. System Overview

EventFlow Management System is a web-based event operations platform that centralizes event creation, publication, registration, attendee management, ticketing, QR check-in, agenda, venue information, reporting, notifications, and organization-level access control.

The authoritative business behavior is defined in `docs/PRD.md`.

The system shall support the following MVP lifecycle:

```text
Authenticated Organizer
        ↓
Organization
        ↓
Create Event
        ↓
Configure Event / Venue / Agenda
        ↓
Configure Registration / Ticket Types
        ↓
Publish Event
        ↓
Public Registration
        ↓
Registration Record
        ↓
Ticket + Non-Sequential QR Identifier
        ↓
Attendee Management
        ↓
Manual / QR Check-In
        ↓
Attendance
        ↓
Dashboard / Report / Export
```

The application shall be implemented as a **modular monolith** to minimize operational complexity while preserving clear business boundaries.

The MVP shall not require:

- Microservices
- Kubernetes
- Event streaming infrastructure
- Elasticsearch
- Native mobile applications
- Separate tenant databases
- Public API
- Webhook delivery platform
- Online payment processing
- Complex CRM
- AI services

---

# 2. System Context

## 2.1 Actors

The system shall interact with:

1. Owner
2. Admin
3. Event Manager
4. Staff
5. Viewer
6. Public Attendee
7. Email delivery service
8. File storage
9. MySQL database
10. Optional queue worker
11. VPS scheduler / cron service

## 2.2 Context Diagram

```text
                   ┌────────────────────┐
                   │ Owner / Admin      │
                   └─────────┬──────────┘
                             │
                 ┌───────────▼───────────┐
                 │                       │
 Event Manager ─►│      EventFlow        │◄─ Staff
                 │     Web Platform      │
 Viewer ────────►│                       │
                 └───────┬───────┬───────┘
                         │       │
              ┌──────────▼─┐   ┌─▼─────────────┐
              │   MySQL    │   │ File Storage  │
              └────────────┘   └───────────────┘
                         │
                 ┌───────▼────────┐
                 │ Queue / Mail   │
                 └───────┬────────┘
                         │
                 ┌───────▼────────┐
                 │ Email Provider │
                 └────────────────┘

 Public Attendee
        │
        ▼
 Public Event Page
        │
        ├── Registration
        └── Ticket / QR
```

## 2.3 Trust Boundaries

The application shall treat the following as distinct trust boundaries:

- Public unauthenticated routes
- Authenticated application routes
- Organization-scoped data
- Event-scoped data
- File access
- Administrative actions
- Background queue execution
- External mail delivery

Public knowledge of an organization ID, event ID, registration ID, or URL shall never be sufficient to obtain unauthorized private data.

---

# 3. Technical Objectives

The technical solution shall:

**TO-001** Preserve all product behavior defined by the PRD.

**TO-002** Keep the initial deployment operable on a conventional VPS.

**TO-003** Prefer Laravel-native features before introducing third-party packages.

**TO-004** Keep business logic testable outside UI rendering concerns.

**TO-005** Enforce organization isolation consistently.

**TO-006** support event workloads of at least 10,000 attendees per event as required by the PRD.

**TO-007** Preserve reliable registration capacity handling under concurrent requests.

**TO-008** Preserve reliable single-attendance semantics during duplicate check-in attempts.

**TO-009** Support future SaaS evolution without implementing premature multi-database tenancy.

**TO-010** Support commercial source-code distribution with predictable configuration and upgrades.

**TO-011** Avoid tightly coupling critical business workflows to optional external services.

**TO-012** Allow background processing for non-critical or slow operations without making queue infrastructure unnecessarily complex.

---

# 4. Functional Requirements

This section translates the PRD functional requirements into software responsibilities. PRD identifiers remain authoritative.

## 4.1 Authentication

The system shall implement:

- Login
- Logout
- Password reset request
- Password reset
- Authenticated profile view/update

Technical behavior:

**SFR-AUTH-001** Authentication shall be handled through Laravel-compatible session authentication for the web application.

**SFR-AUTH-002** Passwords shall be stored only as secure one-way hashes.

**SFR-AUTH-003** Authentication state shall be established through the session layer.

**SFR-AUTH-004** Password reset tokens shall expire.

**SFR-AUTH-005** Used password reset tokens shall not remain reusable.

## 4.2 Organization

**SFR-ORG-001** Every event query performed through protected application features shall be organization-scoped.

**SFR-ORG-002** Organization membership shall define whether an authenticated user may enter an organization workspace.

**SFR-ORG-003** Membership records shall carry the user's organization role.

**SFR-ORG-004** Removing membership shall prevent future access without deleting historical audit identity.

## 4.3 Event

**SFR-EVT-001** Event status shall support Draft, Published, Ongoing, Completed, Cancelled, and Archived.

**SFR-EVT-002** Publishing shall execute a central publish-readiness validation.

**SFR-EVT-003** Public event routes shall only resolve publicly visible events.

**SFR-EVT-004** Internal event lookups shall require authorization in addition to route/model resolution.

## 4.4 Registration

**SFR-REG-001** Registration availability shall be derived from event status, registration enabled state, registration period, ticket availability, and capacity.

**SFR-REG-002** Registration creation and capacity consumption shall occur in a safe transaction boundary.

**SFR-REG-003** A failed notification shall not roll back a completed registration.

**SFR-REG-004** Custom field definitions shall be version-tolerant so historical registration answers remain interpretable even if field configuration changes later.

## 4.5 Ticketing

**SFR-TKT-001** A ticket shall use an unpredictable public identifier or token.

**SFR-TKT-002** QR data shall not expose privileged internal information.

**SFR-TKT-003** Ticket uniqueness shall be enforced by the database.

## 4.6 Check-In

**SFR-CHK-001** Check-in shall verify organization/event access, ticket validity, registration eligibility, and existing attendance state.

**SFR-CHK-002** Duplicate check-in attempts shall return the existing check-in state and shall not create additional attendance rows.

**SFR-CHK-003** Check-in writes shall be atomic.

**SFR-CHK-004** QR and manual check-in shall use the same underlying business rule.

## 4.7 Dashboard

**SFR-DSH-001** Dashboard metrics shall be derived from authoritative event, registration, ticket, and check-in records.

**SFR-DSH-002** Dashboard queries shall be permission-scoped.

**SFR-DSH-003** Cached dashboard values, when used, shall be invalidated after relevant data changes or allowed to expire within an explicitly defined short TTL.

## 4.8 Reporting

**SFR-RPT-001** Report totals shall reconcile with underlying filtered records.

**SFR-RPT-002** Reporting queries shall not require copying operational records into a separate analytics system for the MVP.

## 4.9 Notification

**SFR-NOT-001** Registration confirmation shall be dispatchable independently of the registration transaction.

**SFR-NOT-002** Notification failure shall be logged and retryable when queues are enabled.

## 4.10 Audit

**SFR-AUD-001** Audited actions listed in the PRD shall create immutable application-level audit entries.

**SFR-AUD-002** Audit capture shall not rely exclusively on HTTP request logs.

---

# 5. Non-Functional Requirements

The non-functional requirements from the PRD shall be preserved.

## 5.1 Usability

- Public registration must work on common mobile viewports.
- Check-in must be usable on mobile browser devices with camera permission.
- User-visible operations must show success or failure feedback.
- Destructive actions require confirmation.

## 5.2 Reliability

**NFR-SYS-001** Registration and check-in data integrity shall take precedence over notification delivery.

**NFR-SYS-002** Duplicate browser submissions shall not silently create duplicate business outcomes where prevention is reasonably possible.

**NFR-SYS-003** Database uniqueness and transactional constraints shall supplement application validation for critical invariants.

## 5.3 Availability

The MVP shall remain functional for core management tasks even if:

- Email delivery is temporarily unavailable.
- Queue workers are temporarily unavailable, except queued non-critical tasks remain pending.
- Optional cache storage is unavailable and fallback behavior is configured.

## 5.4 Compatibility

The application shall target:

- Modern Chromium-based browsers
- Modern Firefox
- Common modern mobile browsers
- HTTPS production environments

QR camera scanning requires browser camera support and secure-context permission.

---

# 6. Application Architecture

## 6.1 Architectural Style

The application shall use a **Laravel modular monolith**.

```text
Browser
   │
   ├── Blade Views
   ├── Livewire Components
   └── Alpine.js Enhancements
          │
          ▼
HTTP / Livewire Boundary
          │
          ▼
Authorization + Validation
          │
          ▼
Application Actions / Services
          │
          ▼
Eloquent Models + Query Objects
          │
          ▼
MySQL
```

Background work:

```text
Application
   │
   └── Jobs / Notifications / Mail
             │
             ▼
           Queue
             │
             ▼
        Queue Worker
```

## 6.2 Architectural Principles

1. Use Laravel conventions first.
2. Keep controllers and Livewire components focused on request/UI orchestration.
3. Put reusable business workflows in action/service classes.
4. Put authorization in policies/gates rather than scattered UI checks.
5. Let Eloquent serve as the default persistence abstraction.
6. Avoid custom repository layers unless a concrete need exists.
7. Use database constraints for critical data invariants.
8. Use events/jobs only when they reduce coupling or move slow work out of requests.
9. Do not split into microservices in MVP.
10. Do not introduce infrastructure that is not justified by measured requirements.

## 6.3 Suggested Logical Boundaries

The application may organize responsibilities around:

- Identity
- Organizations
- Events
- Registration
- Ticketing
- Check-In
- Notifications
- Reporting
- Files
- Audit
- Settings

These are logical product boundaries, not separate deployable services.

---

# 7. Authentication Requirements

## 7.1 Authentication Mechanism

The web application shall use Laravel-compatible session authentication.

## 7.2 Credentials

**AUTH-TECH-001** Email shall be normalized consistently before account lookup.

**AUTH-TECH-002** Password hashing shall use Laravel's configured secure password hashing mechanism.

**AUTH-TECH-003** Password reset tokens shall have configured expiry.

**AUTH-TECH-004** Authentication failures shall not disclose whether an email belongs to a privileged role.

## 7.3 Login Protection

The application shall apply request throttling to login attempts.

## 7.4 Attendee Authentication

Attendees shall not require an account for MVP public registration.

Attendee ticket access shall therefore use a secure public mechanism such as:

- signed URL,
- high-entropy access token,
- or another non-guessable reference.

The mechanism selected shall not expose another attendee's registration.

## 7.5 Future Authentication

Future versions may add:

- email verification,
- two-factor authentication,
- SSO,
- attendee accounts.

These are not required for the MVP unless separately approved.

---

# 8. Authorization Requirements

## 8.1 Strategy

Laravel Policies and Gates shall be the primary authorization mechanism.

UI visibility shall not be treated as authorization.

Every protected server-side action shall independently verify permission.

## 8.2 Authorization Dimensions

Authorization shall consider:

1. Authentication status
2. Organization membership
3. Organization role
4. Event access
5. Action capability
6. Resource ownership/scope

## 8.3 Organization Isolation

**AUTHZ-001** A query for protected event data shall never return another organization's records unless the user is explicitly authorized.

**AUTHZ-002** Route-model binding shall not replace organization-scope authorization.

**AUTHZ-003** Export and report queries shall apply the same authorization rules as UI lists.

**AUTHZ-004** File access shall apply organization/event authorization where files are private.

## 8.4 Role Simplicity

The MVP shall use fixed product roles defined by the PRD.

A general-purpose dynamic permission-builder shall not be required in MVP.

---

# 9. User Role Architecture

The authenticated role model shall include:

- Owner
- Admin
- Event Manager
- Staff
- Viewer

Attendee is a public product persona, not necessarily an authenticated application role.

## 9.1 Role Storage

Organization role shall be associated with membership rather than treated only as a global user property.

A user may therefore belong to multiple organizations in future without requiring duplicate user accounts.

## 9.2 Role Enforcement

Policies shall map business actions to role permissions based on the PRD permission matrix.

## 9.3 Owner Protection

**ROLE-001** An active organization shall not be left with zero Owners.

**ROLE-002** An Owner-removal or ownership-transfer workflow shall validate the remaining Owner count.

## 9.4 Event-Level Scope

Event Manager, Staff, and Viewer access may be constrained to assigned events where assignment is enabled.

The MVP shall avoid arbitrary per-field permission configuration.

---

# 10. Session Management

## 10.1 Recommended Production Session Driver

For a new VPS deployment, database-backed sessions are recommended to:

- remain Laravel-native,
- allow session inspection/revocation,
- avoid dependency on Redis,
- ease future multi-instance deployment compared with local file-only sessions.

Actual driver remains environment-configurable and must be verified after project initialization.

## 10.2 Requirements

**SES-001** Session cookies shall be secure in production HTTPS environments.

**SES-002** Session cookies shall be HTTP-only.

**SES-003** SameSite policy shall be configured to support normal first-party application use.

**SES-004** Session identifiers shall regenerate after successful authentication.

**SES-005** Logout shall invalidate the active session.

**SES-006** Session expiry shall be configurable.

## 10.3 Scaling

If multiple web nodes are introduced later, sessions shall use a shared backend such as:

- shared database, or
- Redis.

Local file sessions shall not be used across multiple independent web nodes.

---

# 11. Database Requirements

## 11.1 Database Engine

Target database: MySQL 8.4.x LTS.

Actual installed version: **TBD — Requires Environment Verification**.

## 11.2 Core Logical Entities

The MVP database shall support at minimum:

- users
- password reset data
- sessions where database sessions are enabled
- organizations
- organization memberships
- events
- venues
- agenda items
- registration field definitions
- ticket types
- registrations
- registration field answers
- tickets
- check-ins
- notifications / delivery state where stored
- activity logs
- file metadata where required
- settings where required

Exact table names are a database design concern and may be refined in `docs/DATABASE.md`.

## 11.3 Referential Integrity

**DB-001** Every event shall reference exactly one organization.

**DB-002** Every organization membership shall reference one user and one organization.

**DB-003** Every ticket shall reference one registration.

**DB-004** Every check-in shall reference the relevant event/registration or ticket according to final schema.

**DB-005** Foreign keys shall be used where deletion semantics are well defined.

## 11.4 Uniqueness

Database constraints shall enforce uniqueness for:

- public event slug within its required scope,
- unique ticket identifier/token,
- organization membership pair where duplicates are not meaningful,
- other business keys identified in database design.

## 11.5 Identifiers

Internal primary key strategy shall remain conventional and efficient.

Public identifiers that could expose private records shall be non-sequential and non-guessable.

QR tokens shall not be derived from raw sequential primary keys.

## 11.6 Date/Time

**DB-TIME-001** System timestamps shall be stored in a consistent canonical timezone, preferably UTC.

**DB-TIME-002** Event and registration dates shall be presented in the configured organization/event timezone.

**DB-TIME-003** Timezone information shall not be inferred from the viewer's browser for authoritative business calculations unless explicitly designed.

## 11.7 Money

If informational ticket prices are stored before online payment exists:

- use fixed precision decimal values,
- store or associate an explicit currency code,
- do not use floating point for monetary values.

## 11.8 Indexing

Indexes shall be created for high-frequency filtering and lookup fields including, where applicable:

- organization_id
- event_id
- event status
- public event slug
- registration status
- ticket type
- attendee email
- registration identifier
- ticket identifier/token lookup
- check-in status/timestamp
- created_at for operational lists

Indexes shall be based on actual query patterns rather than speculative indexing of every column.

## 11.9 Soft Deletes

Soft deletes shall be used selectively.

They shall not be added to every table by default.

Records requiring historical reporting or recovery may use archival/status behavior where that better matches the PRD.

---

# 12. Validation Strategy

Validation shall use multiple complementary layers.

## 12.1 Request Boundary Validation

Use Laravel-native validation through:

- Form Request classes for HTTP actions where appropriate,
- Livewire validation for Livewire forms,
- reusable custom validation Rules only where built-in rules are insufficient.

## 12.2 Business Validation

Rules that depend on domain state shall not exist only as UI field validation.

Examples:

- event may be published,
- registration is currently open,
- ticket capacity remains,
- ticket is eligible for check-in,
- organization still has an Owner.

These shall be validated in business action/service logic.

## 12.3 Database Validation

Database constraints shall protect critical invariants such as uniqueness and foreign-key relationships.

## 12.4 Client-Side Validation

Alpine.js or browser-side validation may improve UX but shall never be authoritative.

## 12.5 Validation Output

Validation errors shall:

- be user-readable,
- identify the relevant field or action,
- avoid raw exception details,
- preserve entered non-sensitive form values when appropriate.

---

# 13. Business Logic Strategy

## 13.1 Principle

Business rules shall have one authoritative execution path per use case.

The same rule shall not be reimplemented independently in:

- controller,
- Livewire component,
- queue job,
- console command.

## 13.2 Action-Oriented Workflows

Important use cases should be represented by dedicated application actions/services, for example conceptually:

- Create Event
- Publish Event
- Register Attendee
- Issue Ticket
- Check In Attendee
- Change Registration Status
- Export Attendees
- Remove Organization Member

The names above describe responsibilities and do not prescribe class implementation.

## 13.3 Model Responsibilities

Eloquent models may contain:

- relationships,
- casts,
- simple state helpers,
- local scopes,
- small invariants tightly coupled to the model.

Large workflows spanning several models should not be placed in model event hooks alone.

## 13.4 Domain Events

Application/domain events may be used for secondary consequences such as:

- registration completed,
- ticket issued,
- attendee checked in.

Events shall not obscure the main transactional business decision.

---

# 14. Service Layer Strategy

A selective service/action layer shall be used.

## 14.1 Required for Complex Workflows

A service/action is justified when the use case:

- spans multiple models,
- requires a transaction,
- is called from more than one delivery mechanism,
- has important business rules,
- triggers background work,
- requires independent testing.

## 14.2 Not Required for Simple Read/CRUD

Simple data display or low-risk single-model updates do not require a service solely for architectural purity.

## 14.3 Dependency Direction

UI components shall depend on application/business services rather than services depending on Livewire or Blade.

This allows future reuse from:

- console commands,
- API endpoints,
- scheduled jobs.

---

# 15. Repository Strategy if Required

## 15.1 Default Decision

**No custom repository layer is required for the MVP.**

Eloquent is the default persistence/data-access abstraction.

## 15.2 Why

Adding a repository interface for every model would:

- duplicate Eloquent behavior,
- add maintenance overhead,
- increase indirection,
- provide little value for the current single-database architecture.

## 15.3 When a Repository May Be Introduced

A repository or dedicated data gateway may be justified if:

1. data comes from multiple persistence sources,
2. an external service replaces part of local persistence,
3. a complex read model is reused widely,
4. persistence must be swapped independently,
5. a bounded subsystem has materially different storage requirements.

## 15.4 Query Objects

Dedicated query classes/scopes may be preferred over generic repositories for:

- dashboard metrics,
- reporting,
- complex attendee filtering,
- export datasets.

---

# 16. Transaction Management

Database transactions are mandatory for business operations where partial completion could corrupt state.

## 16.1 Registration Transaction

The registration workflow shall ensure that:

1. registration eligibility is checked,
2. capacity is verified safely,
3. registration is created,
4. ticket is issued when applicable,
5. critical database state commits together.

Notification dispatch shall occur after successful commit or use after-commit behavior.

## 16.2 Capacity Concurrency

Application-only `count()` checks are insufficient for high-contention capacity limits.

The implementation shall use a database-safe strategy such as transactional locking or another atomic capacity mechanism.

The selected approach shall be verified through concurrent registration tests.

## 16.3 Check-In Transaction

Check-in shall atomically:

- validate eligible ticket/registration,
- detect previous check-in,
- create the attendance record once.

A database uniqueness rule shall support the one-attendance invariant where compatible with final schema.

## 16.4 Membership Changes

Owner-removal/role-change operations affecting organization ownership shall execute atomically.

## 16.5 Transaction Scope

Transactions shall remain short and shall not include slow external operations such as email delivery.

---

# 17. Queue Architecture

## 17.1 MVP Queue Strategy

Use Laravel's native queue abstraction.

For a simple single-VPS MVP, the recommended initial production backend is the **database queue driver**, because it avoids requiring Redis solely for queueing.

Actual queue driver: **TBD — Requires Environment Verification**.

## 17.2 Queue Candidates

Background jobs may include:

- registration confirmation email
- ticket email
- event reminder
- large export generation if later required
- image processing if later required
- non-critical report generation if later required

## 17.3 Queue Rules

**QUE-001** Registration success shall not depend on immediate mail delivery.

**QUE-002** Jobs shall be safe to retry or explicitly guard against duplicated side effects.

**QUE-003** Failed jobs shall be observable.

**QUE-004** Job timeouts and retry limits shall be configured according to job type.

**QUE-005** Jobs that depend on newly committed records shall execute only after the relevant transaction is committed.

## 17.4 Redis

Redis is optional for MVP.

Redis may replace database queueing when:

- throughput requires it,
- distributed locks are needed,
- queue latency becomes operationally important,
- multiple application nodes are introduced.

---

# 18. Scheduled Jobs

Laravel Scheduler shall be used for recurring application jobs.

## 18.1 VPS Scheduler

The VPS shall invoke the Laravel scheduler through one operating-system cron entry or equivalent process.

## 18.2 MVP Scheduled Tasks

Potential scheduled tasks include:

- event reminders,
- registration opening/closing side effects if required,
- event status maintenance if automated,
- cleanup of expired temporary data,
- retry/maintenance tasks supported by Laravel,
- backup orchestration if implemented at application level.

## 18.3 Requirements

**SCH-001** Scheduled jobs shall be idempotent where repeat execution is possible.

**SCH-002** Time-based event calculations shall use the configured event/organization timezone consistently.

**SCH-003** A failed scheduled notification shall not alter event registration data.

**SCH-004** Long-running scheduled work should dispatch queue jobs rather than block the scheduler.

---

# 19. Cache Strategy

## 19.1 Principle

Cache shall improve performance but shall not be the authoritative source for transactional event state.

## 19.2 MVP

For a single-VPS MVP:

- Laravel file or database cache may be used,
- Redis is optional.

Actual cache driver: **TBD — Requires Environment Verification**.

## 19.3 Cache Candidates

Suitable data includes:

- organization dashboard aggregates,
- event dashboard aggregates,
- relatively static public event data,
- configuration/reference values.

## 19.4 Do Not Cache as Authority

The following shall be read from authoritative transactional data when making decisions:

- remaining ticket capacity,
- ticket validity,
- check-in status,
- permissions,
- registration eligibility.

## 19.5 Invalidation

Cache keys shall be scoped by organization/event.

Changes to registrations, check-ins, event status, or ticket configuration shall invalidate relevant cached metrics or allow a short explicitly acceptable TTL.

---

# 20. Notification Architecture

Laravel Notifications shall be preferred where they fit the product requirement.

## 20.1 MVP Channels

Required channel:

- Email

Database notifications are optional for MVP unless the UI includes an in-app notification center.

## 20.2 Notification Events

MVP notification events include:

- password reset,
- organization invitation if enabled,
- registration confirmation,
- ticket availability,
- event reminder if enabled.

## 20.3 Separation

Notification delivery shall be separated from transactional state changes.

## 20.4 Retry

Queued notifications shall support retry according to queue policy.

## 20.5 Future Channels

Future modules may add:

- WhatsApp
- SMS
- push notification
- in-app notification

These shall not be required dependencies for core MVP behavior.

---

# 21. Email Architecture

## 21.1 Mail Transport

Email transport shall be environment-configurable through Laravel mail configuration.

No single provider shall be hard-coded into business logic.

## 21.2 Email Types

The MVP shall support:

- password reset email,
- registration confirmation email,
- ticket email or ticket-access email,
- organization invitation where applicable,
- reminder email where enabled.

## 21.3 Email Requirements

**MAIL-001** Email templates shall identify the EventFlow/organization context appropriately.

**MAIL-002** Ticket emails shall reference only the intended attendee ticket.

**MAIL-003** Email failures shall be logged.

**MAIL-004** Email delivery shall be queueable.

**MAIL-005** Environment-specific sender address/name shall be configurable.

## 21.4 Development Safety

Development and testing environments shall use a safe mail transport or controlled recipient mechanism to prevent unintended external delivery.

---

# 22. File Storage

## 22.1 Storage Abstraction

Laravel Filesystem shall be used so the application is not coupled to one storage provider.

## 22.2 MVP Storage Targets

Initial VPS deployment may use local persistent storage.

Future SaaS deployment may use S3-compatible object storage without changing product behavior.

## 22.3 File Categories

MVP may include:

- organization logo,
- event banner,
- event supporting image.

## 22.4 File Rules

**FS-001** Allowed MIME types/extensions shall be explicitly defined.

**FS-002** Maximum file size shall be configurable.

**FS-003** Public assets and private files shall be distinguished.

**FS-004** File names exposed publicly shall not need to reveal original local paths.

**FS-005** Private file delivery shall enforce authorization or signed access.

**FS-006** File replacement shall update active metadata consistently.

## 22.5 Persistence

VPS deployment procedures shall ensure application uploads are not lost during routine code deployment.

---

# 23. Logging

Laravel's logging facilities shall be used.

## 23.1 Application Logging

Production logs shall capture:

- unhandled exceptions,
- queue job failures,
- mail delivery errors,
- scheduler errors,
- integration errors,
- relevant security/authorization anomalies without excessive sensitive data.

## 23.2 Log Content

Logs shall include contextual identifiers where safe, such as:

- request/correlation identifier where implemented,
- user identifier,
- organization identifier,
- event identifier,
- job name.

## 23.3 Sensitive Data

Logs shall not intentionally include:

- plaintext passwords,
- reset tokens,
- full private QR access tokens,
- full session cookies,
- application secrets.

## 23.4 Rotation

Production logs shall be rotated or otherwise managed so disk consumption is bounded.

---

# 24. Activity Logging

Application activity logs are a business audit feature and are distinct from system logs.

## 24.1 Required Audited Actions

The system shall satisfy the PRD audit requirements including:

- event creation/update/publication/cancellation/archive,
- organization setting changes,
- role changes,
- member removal,
- manual registration status changes,
- check-in,
- supported check-in reversal,
- critical ticket/attendee state changes.

## 24.2 Audit Data

Audit records shall contain:

- action type,
- timestamp,
- actor identifier,
- organization identifier,
- related entity type,
- related entity identifier,
- human-readable summary where applicable.

## 24.3 Immutability

Normal application roles shall not be able to edit audit history.

Deletion policy for activity logs shall be defined separately from routine operational-record deletion.

## 24.4 Dependency Strategy

A third-party activity logging package is not mandatory.

Laravel-native/custom audit persistence is acceptable if it meets all requirements.

A package may be selected only if it materially reduces risk and is actively compatible with the verified Laravel version.

---

# 25. Error Handling

## 25.1 General Strategy

The application shall distinguish:

- validation errors,
- authentication failures,
- authorization failures,
- not-found errors,
- business rule conflicts,
- external service failures,
- unexpected system failures.

## 25.2 User-Facing Behavior

Users shall receive:

- concise message,
- actionable explanation where possible,
- no stack trace,
- no SQL details,
- no secrets.

## 25.3 Business Conflict Examples

Controlled business errors include:

- registration closed,
- ticket sold out,
- event cancelled,
- ticket invalid,
- ticket already checked in,
- user not authorized,
- required publish data missing.

## 25.4 Exception Handling

Unexpected exceptions shall:

1. be logged,
2. return a safe production response,
3. not expose sensitive internals.

## 25.5 Transaction Failures

Failed transactional operations shall roll back partial writes.

---

# 26. API Requirements

## 26.1 MVP Decision

A public REST API is **out of MVP scope**.

The Blade/Livewire web application shall not require a separate API merely to communicate with itself.

## 26.2 Internal Endpoints

Specific web endpoints may exist for:

- QR validation/check-in,
- downloads,
- Livewire requests,
- AJAX-like interactions.

These are application endpoints and shall follow the same authentication/authorization requirements.

## 26.3 Future Public API

If a public API is approved later:

- use versioned routes such as `/api/v1`,
- use Laravel-native authentication appropriate to the client type,
- consider Laravel Sanctum for token-based first-party/integration access,
- apply rate limits,
- apply organization scoping,
- publish API documentation,
- maintain backward compatibility within an API major version.

No public API requirement shall delay MVP release.

---

# 27. Webhook Requirements if Applicable

## 27.1 MVP Decision

Outbound and inbound product webhooks are **not required in the MVP**.

## 27.2 Future Requirements

If webhooks are introduced:

**WH-001** Outbound webhook payloads shall be versioned.

**WH-002** Delivery shall be queued.

**WH-003** Failed delivery shall be retryable with bounded retry policy.

**WH-004** Webhook secrets shall be stored securely.

**WH-005** Outbound requests shall support signature verification by recipients.

**WH-006** Inbound webhooks shall verify authenticity before changing business state.

**WH-007** Idempotency shall be implemented for state-changing inbound webhook events.

---

# 28. Search Architecture

## 28.1 MVP Decision

Use MySQL-backed search and filtering.

No Elasticsearch, OpenSearch, or Meilisearch dependency is required for MVP.

## 28.2 Search Targets

Event search:

- event name,
- status,
- date range.

Attendee search:

- name,
- email,
- registration identifier,
- ticket identifier where permitted.

## 28.3 Query Strategy

Use:

- normalized searchable values where appropriate,
- targeted SQL indexes,
- pagination,
- scoped queries,
- exact/prefix/partial matching appropriate to field type.

## 28.4 Search Requirements

**SEARCH-001** Search shall always apply organization/event authorization.

**SEARCH-002** Attendee search shall meet the PRD 2-second target for events up to 10,000 attendees under normal conditions.

**SEARCH-003** Filtering shall be composable without loading all records into PHP memory.

**SEARCH-004** Large result sets shall be paginated.

## 28.5 Future Search Engine

A specialized search engine may be introduced only when measured needs exceed MySQL search capabilities.

---

# 29. Import/Export

## 29.1 Export

MVP export shall support CSV for:

- attendees,
- registrations,
- attendance.

## 29.2 Export Architecture

For datasets up to the PRD target of 10,000 rows, exports should use streaming/chunked data access where necessary to avoid excessive browser/server memory use.

Exports shall not load an unbounded dataset into memory.

## 29.3 Filtered Export

Export queries shall reuse the same authorized filter logic as the corresponding list/report where applicable.

## 29.4 Import

Bulk attendee import remains optional/deferred under the PRD.

If introduced:

- use a documented CSV template,
- validate rows,
- provide error feedback,
- prevent silent data loss,
- process large imports through queue jobs where required.

## 29.5 Package Strategy

A spreadsheet/export package is not automatically required.

Use native CSV generation where it satisfies the MVP.

Add a spreadsheet package only if XLSX or advanced spreadsheet requirements are approved.

---

# 30. Reporting Architecture

## 30.1 MVP Reporting Strategy

Reports shall be generated from operational MySQL data.

A separate data warehouse or BI platform is not required.

## 30.2 Required Reports

- Event summary
- Registration report
- Ticket type report
- Attendance report

## 30.3 Query Design

Reporting shall use:

- aggregate SQL queries,
- reusable query objects/scopes where beneficial,
- pagination for detailed rows,
- scoped filters.

## 30.4 Reconciliation

**REPORT-001** Dashboard totals and report totals shall share consistent metric definitions.

**REPORT-002** Attendance percentage shall use the PRD-defined eligible confirmed attendee denominator.

**REPORT-003** Reports shall not use stale cache for authoritative exported totals unless cache consistency is explicitly guaranteed.

## 30.5 Future Reporting

Advanced report snapshots, scheduled reports, BI exports, and analytics warehouses are future options, not MVP requirements.

---

# 31. Security Architecture

## 31.1 Defense Layers

The application shall use:

1. HTTPS
2. session security
3. CSRF protection
4. server-side validation
5. authorization policies
6. database constraints
7. output escaping
8. rate limiting
9. secure file access
10. safe logging
11. environment secret management
12. backups

## 31.2 CSRF

State-changing browser requests shall use Laravel's CSRF protection.

## 31.3 XSS

Blade escaping shall be used by default.

Rendering user-provided HTML shall be avoided unless a sanitization requirement is explicitly approved.

## 31.4 SQL Injection

Database access shall use Eloquent/query builder bindings or properly bound statements.

User input shall not be concatenated into raw SQL.

## 31.5 Mass Assignment

Writable model fields shall be controlled explicitly according to the chosen Laravel model configuration.

Authorization and validation shall occur before persisted state changes.

## 31.6 Rate Limiting

Rate limits shall be considered for:

- login,
- forgot password,
- public registration,
- check-in validation endpoints,
- future API endpoints.

Public registration throttling shall avoid blocking legitimate event bursts while reducing obvious automated abuse.

## 31.7 Ticket Security

Ticket public identifiers shall:

- be unpredictable,
- not reveal raw sequential database IDs,
- be scoped to intended use.

## 31.8 File Security

Private files shall not be exposed through predictable public paths.

## 31.9 Secrets

Production secrets shall be stored outside source control.

Examples:

- app key,
- database password,
- SMTP password,
- object storage credentials,
- future API secrets.

## 31.10 Production Debugging

Debug mode shall be disabled in production.

---

# 32. Backup Requirements

## 32.1 Scope

Backups shall cover:

- MySQL database
- persistent user-uploaded files
- critical environment/configuration documentation needed for restoration

Source code itself should be version-controlled and shall not be treated as the only backup mechanism for data.

## 32.2 Minimum Production Policy

Recommended minimum for commercial VPS deployments:

- daily database backup,
- daily uploaded-file backup or equivalent snapshot,
- at least 7 daily restore points,
- at least 4 weekly restore points,
- at least one backup copy stored outside the primary VPS.

Exact retention may be changed by commercial/operational policy.

## 32.3 Backup Security

Backups containing private attendee data shall be access-restricted.

## 32.4 Restore Testing

**BKP-001** A backup is not considered operationally valid until a restore procedure is documented.

**BKP-002** Restore testing shall occur before commercial launch and periodically thereafter.

## 32.5 Deployment Backup

Before risky framework/database upgrades:

- create verified database backup,
- preserve uploaded files,
- record current deployed version.

---

# 33. Testing Requirements

## 33.1 Test Runner

Actual test runner/version is **TBD — Requires Environment Verification**.

Use the framework-compatible test tooling selected in the initialized Laravel project.

No test framework version shall be invented before project initialization.

## 33.2 Required Test Types

### Unit Tests

For isolated:

- business calculations,
- state rules,
- value transformations,
- reusable domain logic.

### Feature Tests

For:

- authentication,
- organization isolation,
- event workflows,
- registration,
- ticket issuance,
- check-in,
- permissions,
- reports,
- exports.

### Integration Tests

For:

- database transactions,
- queue behavior,
- mail dispatch,
- file storage,
- scheduler where relevant.

### Browser / End-to-End Tests

Required at least for critical commercial flows if feasible:

- organizer creates/publishes event,
- attendee registration,
- staff check-in,
- mobile registration usability.

## 33.3 PRD Acceptance Test Mapping

The test suite shall include automated or documented acceptance coverage for:

- AC-01 Authentication
- AC-02 Organization Isolation
- AC-03 Event Creation
- AC-04 Event Publication
- AC-05 Public Event Page
- AC-06 Closed Registration
- AC-07 Capacity
- AC-08 Registration
- AC-09 Ticket
- AC-10 Attendee Search
- AC-11 QR Check-In
- AC-12 Duplicate Check-In
- AC-13 Invalid QR
- AC-14 Dashboard
- AC-15 Export
- AC-16 Audit Trail
- AC-17 Permissions
- AC-18 Mobile Registration

## 33.4 Concurrency Tests

Special tests shall cover:

- two registrations competing for the final available capacity,
- repeated registration submission,
- two check-in requests for the same ticket.

## 33.5 Authorization Matrix Tests

Permissions shall be tested for:

- Owner
- Admin
- Event Manager
- Staff
- Viewer

UI hiding alone is not sufficient evidence.

## 33.6 Production Release Gate

No unresolved critical-severity defect may remain for commercial release.

---

# 34. Deployment Requirements

## 34.1 Target

Production deployment target: Linux-based VPS.

Exact operating system, web server, and hosting provider are **TBD — Requires Environment Verification**.

## 34.2 Recommended Production Topology

```text
Internet
   │
 HTTPS
   ▼
Reverse Proxy / Web Server
   │
   ▼
PHP-FPM / Laravel
   │
   ├── MySQL
   ├── Persistent Storage
   ├── Queue Worker
   └── Scheduler
```

For MVP, these may reside on one appropriately sized VPS.

## 34.3 Web Server

Nginx is recommended for a new VPS deployment, but actual web server shall be verified.

Apache may be supported if the project is configured accordingly.

## 34.4 Process Requirements

Production shall have:

- web server process,
- PHP-FPM,
- MySQL,
- queue worker if queued jobs are enabled,
- scheduler cron entry,
- log rotation,
- backup execution,
- TLS certificate.

## 34.5 Queue Worker Management

Queue workers shall run under a process supervisor or service manager so they restart after failure/reboot.

## 34.6 Deployment Procedure

A production release shall include:

1. maintenance/traffic strategy where needed,
2. application backup for risky changes,
3. code deployment,
4. dependency installation from lockfiles,
5. database migration,
6. frontend asset build/deployment,
7. configuration/cache refresh,
8. worker restart,
9. health/smoke checks.

## 34.7 Zero-Downtime

Zero-downtime deployment is not an MVP requirement.

However, migrations should avoid unnecessary destructive changes that make rollback impossible.

---

# 35. Environment Configuration

## 35.1 Environment Separation

At minimum:

- local/development
- testing
- production

Staging is recommended before commercial release.

## 35.2 Configuration Categories

Environment configuration shall cover:

- application name/environment/debug mode
- application URL
- application key
- timezone/default locale
- database
- session
- cache
- queue
- filesystem
- mail
- logging
- backup destinations
- feature switches where justified

## 35.3 Secrets

Secrets shall not be committed to source control.

`.env.example` shall document required keys without real secrets once the project is initialized.

## 35.4 Configuration Validation

Deployment documentation shall identify required environment variables.

Missing critical production configuration shall fail visibly rather than silently using unsafe defaults.

## 35.5 Commercial Distribution

For source-code customers, environment setup documentation shall distinguish:

- mandatory configuration,
- optional integrations,
- production-recommended settings.

---

# 36. Performance Requirements

The PRD performance requirements are authoritative.

## 36.1 Page Response

**PERF-SRS-001** Standard authenticated pages should complete within 3 seconds under representative normal production conditions.

## 36.2 Search

**PERF-SRS-002** Attendee search shall return within 2 seconds for events up to 10,000 attendees under normal conditions.

## 36.3 QR Check-In

**PERF-SRS-003** QR validation/check-in result shall complete within 3 seconds under normal conditions.

## 36.4 Dashboard

**PERF-SRS-004** Dashboard shall load within 4 seconds for an organization with up to:

- 100 events,
- 100,000 total historical registrations,

under representative hosting conditions.

## 36.5 Export

**PERF-SRS-005** CSV export of 10,000 attendee rows shall complete without browser memory failure.

## 36.6 Performance Design Rules

To meet the targets:

- use database indexes,
- paginate lists,
- avoid N+1 queries,
- eager-load intentionally,
- aggregate in SQL where appropriate,
- stream/chunk exports,
- cache read-heavy dashboard values where safe,
- move slow non-critical work to queues,
- profile before adding infrastructure.

## 36.7 Performance Verification

Performance claims shall be validated with representative seeded data before commercial release.

---

# 37. Scalability Requirements

## 37.1 MVP Scaling Model

Scale vertically first on a VPS.

Do not introduce distributed architecture until measured workload requires it.

## 37.2 Database

The schema and indexes shall support:

- 10,000 attendees per event,
- 100 events per organization dashboard scenario,
- 100,000 historical registrations in the PRD benchmark scenario.

## 37.3 Application Statelessness

Where practical, the application shall avoid local-only state assumptions.

Shared session/cache/storage backends can be introduced later.

## 37.4 Queue Scaling

Queue workers shall be independently scalable from web requests when workload increases.

## 37.5 Storage Scaling

Laravel filesystem abstraction shall allow migration from local VPS storage to S3-compatible storage.

## 37.6 Horizontal Scaling Readiness

Future horizontal scaling may require:

- shared session backend,
- shared cache,
- shared/object file storage,
- Redis queue,
- load balancer,
- separate database server.

These are future infrastructure changes, not MVP dependencies.

## 37.7 Multi-Tenant SaaS

The organization model shall provide logical tenant boundaries.

Complex database-per-tenant architecture shall not be implemented in MVP.

---

# 38. Maintainability Requirements

## 38.1 Laravel Conventions

Prefer conventional directories and framework concepts before custom architecture.

Typical responsibilities shall remain recognizable through:

- Models
- Policies
- Form Requests / validation
- Livewire components
- Jobs
- Notifications / Mail
- Events / Listeners where justified
- Actions / Services for workflows
- Query objects/scopes for complex reads

## 38.2 Dependency Discipline

**MNT-001** Every third-party package shall have a concrete product or technical need.

**MNT-002** Packages duplicating straightforward Laravel-native capability should be avoided.

**MNT-003** Package compatibility with the verified Laravel/PHP version shall be checked before adoption.

**MNT-004** Critical business behavior shall not become impossible to maintain if a non-essential package is removed.

## 38.3 Code Organization

Business domains shall use clear namespaces/folders without forcing full Domain-Driven Design ceremony.

## 38.4 Documentation

Maintain:

- PRD
- SRS
- system design
- database design
- business flow
- UI/UX specification
- deployment documentation
- upgrade notes
- user guide for commercial release

## 38.5 Static Analysis / Formatting

The initialized project should adopt compatible code formatting and static analysis practices, but exact tools/versions shall be selected after environment initialization.

Avoid adding several overlapping quality tools.

## 38.6 Database Migrations

Schema changes shall be implemented through version-controlled Laravel migrations.

Direct undocumented production schema edits are prohibited.

---

# 39. Upgrade Strategy

## 39.1 Version Locking

Production releases shall use lockfiles:

- `composer.lock`
- frontend package lockfile

Dependencies shall not be resolved to arbitrary new versions during production deployment.

## 39.2 Patch/Minor Updates

Patch/minor dependency updates shall be:

1. reviewed,
2. tested,
3. deployed through normal release process.

## 39.3 Laravel Major Upgrades

Laravel major upgrades shall:

1. review official upgrade guidance,
2. verify PHP compatibility,
3. verify package compatibility,
4. run full tests,
5. back up production data,
6. use staged deployment.

## 39.4 Database Upgrade

MySQL LTS patch upgrades shall be preferred over unnecessary movement to innovation releases for a stability-focused commercial product.

## 39.5 Source-Code Customer Strategy

Because customers may modify the source code:

- keep configuration separate from core behavior,
- keep branding/theme customization isolated,
- publish release notes,
- publish migration/upgrade notes,
- minimize destructive schema changes,
- avoid customer customization instructions that require editing framework/vendor files.

## 39.6 Backward Compatibility

Within a commercial major product version:

- avoid needless breaking configuration changes,
- preserve existing data through migrations,
- document behavior changes.

## 39.7 Rollback

Application rollback procedures shall account for database migration compatibility.

A code rollback is not safe if the database schema has been changed incompatibly.

---

# 40. Technical Constraints

The following constraints apply to the MVP unless explicitly changed by product approval.

## 40.1 Framework and Platform

- Backend target is Laravel 13.x.
- PHP target is 8.4.x.
- Database target is MySQL 8.4.x LTS.
- Frontend is Blade + Livewire + Alpine.js + Tailwind CSS.
- Deployment target is VPS.
- Exact installed versions remain TBD until project initialization.

## 40.2 Architecture

- Modular monolith.
- No microservices.
- No Kubernetes requirement.
- No message broker beyond Laravel queue requirements.
- No event-sourcing architecture.
- No CQRS architecture requirement.
- No database-per-tenant MVP architecture.

## 40.3 Persistence

- MySQL is the authoritative transactional datastore.
- Eloquent is the default data-access layer.
- No repository-per-model requirement.
- No separate analytics warehouse in MVP.

## 40.4 Search

- MySQL-based search first.
- No Elasticsearch/OpenSearch/Meilisearch requirement for MVP.

## 40.5 Cache and Queue

- Redis shall not be mandatory for initial deployment.
- Laravel database queue is acceptable for MVP.
- Redis may be introduced when justified by scaling.

## 40.6 External Integrations

MVP shall not depend on:

- payment gateway,
- WhatsApp API,
- SMS provider,
- CRM,
- streaming provider,
- external search service,
- AI provider.

Email is the only required external communication integration.

## 40.7 API/Webhooks

- No public API required in MVP.
- No product webhooks required in MVP.

## 40.8 Mobile

- No native Android app.
- No native iOS app.
- Public registration and check-in must be responsive web experiences.

## 40.9 QR Scanner Constraint

Browser-based camera scanning requires:

- camera-capable client,
- user camera permission,
- browser support,
- secure HTTPS context in production.

Manual search/check-in shall remain available as fallback.

## 40.10 Commercial Constraint

The system must remain suitable for:

- self-hosted deployment,
- source-code sale,
- repeated installations,
- future SaaS packaging.

Architecture shall therefore avoid hidden dependencies on a single vendor-specific runtime where a Laravel-native abstraction is available.

---

# Technical Requirement Traceability Summary

| PRD Area | SRS Technical Coverage |
|---|---|
| Authentication | Sections 4, 7, 10, 31 |
| Organizations / Roles | Sections 4, 8, 9, 11 |
| Events | Sections 4, 11, 13 |
| Public Event Page | Sections 4, 31, 36 |
| Registration | Sections 4, 12, 13, 16 |
| Ticketing | Sections 4, 11, 16, 31 |
| Check-In | Sections 4, 16, 28, 31, 36 |
| Dashboard | Sections 4, 19, 30, 36 |
| Notifications | Sections 17, 20, 21 |
| Reports / Export | Sections 28, 29, 30 |
| Files | Section 22 |
| Audit | Sections 23, 24 |
| Security | Sections 7, 8, 10, 31 |
| Performance | Sections 19, 28, 29, 30, 36 |
| Deployment | Sections 17, 18, 32, 34, 35 |
| Scalability | Section 37 |
| Maintainability | Sections 38, 39 |
| MVP Constraints | Section 40 |

---

# MVP Technical Decision Summary

The recommended EventFlow MVP technical shape is:

```text
                     EVENTFLOW
                         │
             Laravel 13.x Modular Monolith
                         │
       ┌─────────────────┼──────────────────┐
       │                 │                  │
   Blade/Livewire     Application        Laravel Jobs
       │              Actions/Services        │
   Alpine/Tailwind        │                 Queue
       │                  │
       └──────────────► Eloquent
                          │
                       MySQL 8.4
```

Recommended simple production infrastructure:

```text
VPS
├── Nginx (recommended; verify actual environment)
├── PHP 8.4 / PHP-FPM
├── Laravel 13
├── MySQL 8.4 LTS
├── Persistent file storage
├── Database-backed session
├── Database queue
├── Queue worker
├── Laravel scheduler via cron
└── TLS / HTTPS
```

Redis, S3-compatible storage, horizontal web nodes, public API, webhooks, advanced search services, and SaaS-specific infrastructure shall be introduced only when a validated requirement justifies them.

---

**End of Document**
