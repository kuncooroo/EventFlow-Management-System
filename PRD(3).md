# EventFlow Management System — Product Requirements Document (PRD)

**Document Path:** `docs/PRD.md`  
**Product:** EventFlow Management System  
**Document Type:** Product Requirements Document  
**Business Domain:** Event Management / Event Technology / Business Management  
**Primary Platform:** Web Application  
**Planned Technology Context:** Laravel + MySQL  
**Document Status:** Draft for Product Definition  
**Version:** 1.0  

---

# 1. Document Information

## 1.1 Purpose

This Product Requirements Document defines the expected product behavior, scope, users, business rules, functional requirements, non-functional requirements, acceptance criteria, and product boundaries for EventFlow Management System.

The document is intended to serve as the primary product reference for:

- Product management
- Business analysis
- UI/UX design
- Software architecture
- Quality assurance
- Development planning
- Commercial packaging
- Future SaaS expansion

This document focuses on **what the product must do and how users are expected to experience it**. It does not define implementation code.

## 1.2 Product Name

**EventFlow Management System**

## 1.3 Product Description

EventFlow Management System is a web-based event management platform that enables organizations and event teams to plan, publish, manage, monitor, and report event activities from one centralized system.

The product covers event creation, attendee registration, ticketing, schedules, venues, team access, event-day check-in, participant management, reporting, and supporting operational workflows.

## 1.4 Target Market

EventFlow is intended for:

- Professional event organizers
- Companies and corporate teams
- Universities and educational institutions
- Communities
- Foundations and organizations
- Government institutions
- Training providers
- Seminar organizers
- Workshop organizers
- Conference organizers
- Exhibition organizers
- Competition organizers
- Organizations that repeatedly organize events

## 1.5 Product Principles

EventFlow shall prioritize:

1. Simplicity
2. Clear event workflows
3. Low operational friction
4. Commercial viability
5. Repeatable deployment
6. Maintainability
7. Modular product growth
8. Self-hosted readiness
9. SaaS readiness without forcing SaaS complexity into the MVP
10. Product behavior that can be tested objectively

---

# 2. Product Overview

EventFlow centralizes event operations that are commonly scattered across spreadsheets, online forms, messaging applications, documents, and separate ticketing tools.

The primary product workflow is:

```text
Create Event
    ↓
Configure Event
    ↓
Publish Event
    ↓
Open Registration
    ↓
Receive Registrations
    ↓
Issue Ticket / Registration Confirmation
    ↓
Manage Attendees
    ↓
Run Event Check-In
    ↓
Monitor Attendance
    ↓
Generate Reports
    ↓
Complete / Archive Event
```

The MVP shall focus on the operational lifecycle of an event rather than attempting to provide a full enterprise marketing, CRM, streaming, travel, hotel, or event marketplace platform.

---

# 3. Background

Event organizers frequently use multiple disconnected tools to run one event.

A common operational setup includes:

- Spreadsheet for event planning
- Online form for participant registration
- Messaging applications for coordination
- Documents for schedules and rundown
- Separate attendee lists
- Separate QR code generation tools
- Manual attendance sheets
- Separate payment records
- Manual report preparation

This creates duplicated work, inconsistent data, difficulty identifying the latest source of truth, and operational risk during event execution.

EventFlow is intended to become a centralized event operations workspace that reduces tool fragmentation and creates one authoritative record for each event.

---

# 4. Problem Statement

Event organizers and organizations need a centralized system to manage events because current workflows commonly rely on disconnected spreadsheets, forms, messaging applications, and documents.

This fragmentation causes:

- Duplicate participant data
- Inconsistent participant status
- Difficulty tracking event capacity
- Unclear team responsibilities
- Manual ticket creation
- Manual attendance reconciliation
- Slow event reporting
- Human error
- Limited event visibility
- Difficult reuse of previous event configurations

EventFlow must solve these problems by providing one centralized product for event setup, registration, attendee management, ticketing, agenda management, access control, check-in, notifications, and reporting.

---

# 5. Product Vision

## 5.1 Vision Statement

**EventFlow aims to become a simple, reliable, and commercially reusable event operations platform for organizations that have outgrown spreadsheets and disconnected event tools.**

## 5.2 Product Positioning

EventFlow shall be positioned as:

> A simple event operations and registration management platform for organizations that need centralized event workflows without the complexity of enterprise event-management software.

## 5.3 Long-Term Product Direction

The product should be capable of evolving into:

- Commercial self-hosted software
- Source-code product
- Managed SaaS
- White-label platform
- Modular event-management ecosystem

The MVP shall not require all long-term capabilities to be implemented.

---

# 6. Product Objectives

## 6.1 Primary Objectives

EventFlow shall enable an authorized organizer to:

1. Create and configure an event.
2. Publish an event registration page.
3. Accept participant registrations.
4. Manage attendees from one database.
5. Define ticket or registration categories.
6. Issue unique registration confirmation or tickets.
7. Check attendees in during an event.
8. Monitor attendance.
9. Manage event agenda and venue information.
10. Assign controlled access to team members.
11. Export operational data.
12. Generate basic event reports.

## 6.2 Secondary Objectives

The product should:

- Reduce manual event administration.
- Reduce dependency on spreadsheets.
- Improve data consistency.
- Speed up participant verification.
- Improve post-event reporting.
- Allow recurring event organizers to reuse the product repeatedly.
- Provide a foundation for future paid modules.

---

# 7. Success Metrics

Success metrics shall be measured per organization and event.

## 7.1 Product Adoption Metrics

1. At least 80% of created events that reach `Published` status shall have at least one completed registration.
2. At least 70% of published events with registrations shall use the built-in attendee management page.
3. At least 50% of events with attendees shall use EventFlow check-in when event-day check-in is enabled.

## 7.2 Operational Metrics

1. An organizer shall be able to create a basic event in no more than 10 minutes without technical assistance.
2. A standard attendee registration shall require no more than 3 page transitions from event landing page to confirmation.
3. A valid QR check-in shall produce a result in no more than 3 seconds under normal operating conditions.
4. An attendee search shall return results in no more than 2 seconds for an event containing up to 10,000 attendees under normal operating conditions.
5. An organizer shall be able to export an attendee list without external database access.

## 7.3 Data Quality Metrics

1. Each registration shall have a unique system identifier.
2. Each issued ticket shall have a unique ticket identifier.
3. Duplicate check-in attempts shall be detected and displayed to the operator.
4. All status changes defined as auditable actions shall create an audit entry.

## 7.4 Commercial Readiness Metrics

Before version 1.0 is considered commercially releasable:

- Installation documentation must exist.
- Product configuration documentation must exist.
- Core workflows must pass acceptance testing.
- Default branding must be replaceable without editing core business content.
- No unresolved critical-severity defect may remain.

---

# 8. Target Users

## 8.1 Primary Users

- Event organizers
- Event administrators
- Event project managers
- Event staff / committee members

## 8.2 Secondary Users

- Attendees / participants
- Event management or organization leadership
- Vendors where vendor access is enabled in future versions

## 8.3 Buyer Personas

Potential buyers may include:

- Event-organizer business owners
- Company operations managers
- University management
- Training companies
- Software agencies purchasing source code
- Organizations requiring self-hosted event-management software

---

# 9. User Personas

## 9.1 Persona A — Event Organization Owner

**Goal:** Monitor events and delegate operational work.

**Needs:**

- View all events
- View registration performance
- View attendance performance
- Manage organization members
- Access high-level reports
- Control organization settings

**Success condition:** The owner can understand event status without requesting manual reports from staff.

## 9.2 Persona B — Event Manager

**Goal:** Configure and execute an event end-to-end.

**Needs:**

- Create events
- Configure schedules
- Configure registration
- Manage ticket types
- Manage attendees
- Send event updates
- Manage event staff
- Monitor check-in

**Success condition:** The event manager can run the core event workflow without relying on external spreadsheets.

## 9.3 Persona C — Event Staff

**Goal:** Perform assigned operational tasks safely.

**Needs:**

- View assigned events
- Search attendees
- Check attendees in
- View event agenda
- View limited operational information

**Success condition:** Staff can perform event-day responsibilities without receiving administrative privileges.

## 9.4 Persona D — Attendee

**Goal:** Register and attend an event with minimal friction.

**Needs:**

- View event information
- Select available ticket/registration type
- Complete registration
- Receive confirmation
- Access ticket or registration QR code
- Receive event updates

**Success condition:** The attendee can register without creating an account unless the event explicitly requires one in a future version.

## 9.5 Persona E — Management Viewer

**Goal:** Monitor event performance without modifying operational data.

**Needs:**

- View event summary
- View registration totals
- View attendance
- View reports

**Success condition:** Management can access read-only event information without gaining editing permissions.

---

# 10. User Pain Points

EventFlow shall address the following user pain points:

| ID | Pain Point | Product Response |
|---|---|---|
| PP-01 | Registration data is scattered | Central registration database |
| PP-02 | Attendee lists become inconsistent | Single attendee record per registration |
| PP-03 | Event status is difficult to monitor | Event dashboard |
| PP-04 | QR tickets are generated separately | Built-in unique ticket generation |
| PP-05 | Event-day check-in is slow | Search and QR check-in |
| PP-06 | Staff receive excessive access | Role-based permissions |
| PP-07 | Reports are manually reconstructed | Built-in reports and export |
| PP-08 | Agenda changes are difficult to distribute | Central event agenda |
| PP-09 | Capacity is manually tracked | Ticket/event capacity tracking |
| PP-10 | Actions are difficult to trace | Audit trail |

---

# 11. User Roles

The MVP shall support the following default roles.

## 11.1 Owner

Organization-level role with full control over organization and event data.

## 11.2 Admin

Organization-level administrative role with broad event-management access, excluding ownership transfer and restricted organization-level actions.

## 11.3 Event Manager

Event-level operational role that can create and manage assigned events.

## 11.4 Staff

Event-level operational role with limited access to assigned event tasks such as attendee lookup and check-in.

## 11.5 Viewer

Read-only event-level role.

## 11.6 Attendee

Public participant role. Attendees do not require an authenticated dashboard account in the MVP.

---

# 12. Product Scope

The initial product shall include:

- Authentication
- User profile
- Organization/workspace
- Organization members
- Role assignment
- Event management
- Public event page
- Venue information
- Agenda/sessions
- Registration configuration
- Registration form
- Custom registration fields
- Attendee management
- Ticket types
- Ticket generation
- QR code
- Manual check-in
- QR check-in
- Attendance monitoring
- Basic notifications
- Dashboard
- Reports
- Search and filtering
- CSV/Excel-compatible export
- File uploads
- Activity/audit trail
- Basic localization readiness
- Basic system settings

---

# 13. MVP Scope

## 13.1 MVP Golden Path

```text
Organizer Login
    ↓
Create Organization / Join Organization
    ↓
Create Event
    ↓
Configure Event
    ↓
Create Ticket Type
    ↓
Configure Registration
    ↓
Publish Event
    ↓
Attendee Registers
    ↓
Registration Confirmed
    ↓
Ticket / QR Issued
    ↓
Organizer Manages Attendees
    ↓
Staff Checks In Attendee
    ↓
Attendance Recorded
    ↓
Organizer Exports / Reviews Report
```

## 13.2 MVP Modules

The MVP shall include:

1. Authentication
2. Organization
3. Members and basic roles
4. Events
5. Venues
6. Agenda
7. Public event page
8. Registration
9. Custom registration fields
10. Attendees
11. Ticket types
12. Tickets and QR codes
13. Check-in
14. Dashboard
15. Basic email notifications
16. Reports
17. Export
18. Activity logs
19. Settings

---

# 14. Out of Scope

The following shall not be included in the MVP:

- Native Android application
- Native iOS application
- Live streaming
- Video conferencing
- AI event planner
- AI chatbot
- Attendee social network
- AI attendee matchmaking
- Gamification platform
- Interactive venue floor plan
- Complex seat reservation
- Hotel booking
- Travel booking
- Flight booking
- Full accounting
- Payroll
- Full CRM
- Marketing automation suite
- Marketplace discovery engine
- Multi-vendor marketplace payout
- Advanced sponsor marketplace
- Advanced vendor procurement
- Multiple payment gateway integrations
- Advanced refund automation
- Enterprise SSO
- Complex tenant-isolated databases
- Plugin marketplace
- Public developer marketplace

Online payment is not a required MVP capability unless a later business decision explicitly changes the MVP.

---

# 15. Functional Requirements

## 15.1 Authentication

**FR-AUTH-001** The system shall allow a registered user to log in using a valid email address and password.

**FR-AUTH-002** The system shall reject login attempts with invalid credentials.

**FR-AUTH-003** The system shall allow authenticated users to log out.

**FR-AUTH-004** The system shall provide a forgot-password workflow.

**FR-AUTH-005** The system shall allow a user with a valid reset token to set a new password.

**FR-AUTH-006** The system shall invalidate an expired or previously used password-reset token.

**FR-AUTH-007** The system shall allow authenticated users to view and update their basic profile.

## 15.2 Organization Management

**FR-ORG-001** The system shall allow an authorized user to create an organization/workspace.

**FR-ORG-002** Each event shall belong to exactly one organization.

**FR-ORG-003** The system shall allow an Owner or Admin to view organization members.

**FR-ORG-004** The system shall allow an authorized user to add or invite organization members.

**FR-ORG-005** The system shall allow an authorized user to assign supported roles to members.

**FR-ORG-006** The system shall prevent a user from accessing an organization for which the user has no valid membership.

## 15.3 Event Management

**FR-EVT-001** An authorized user shall be able to create an event.

**FR-EVT-002** An event shall have a unique system identifier.

**FR-EVT-003** An event shall contain at minimum: name, start date/time, end date/time, organizer, and status before it can be published.

**FR-EVT-004** An event may contain: description, banner, contact information, venue, capacity, registration period, and public slug.

**FR-EVT-005** The system shall support the statuses: Draft, Published, Ongoing, Completed, Cancelled, and Archived.

**FR-EVT-006** Draft events shall not be publicly discoverable through their public event URL.

**FR-EVT-007** Published events shall be accessible through their public event URL.

**FR-EVT-008** The system shall prevent publication when mandatory publish requirements are not satisfied.

**FR-EVT-009** An authorized user shall be able to update an event.

**FR-EVT-010** The system shall record auditable event status changes.

**FR-EVT-011** A completed event shall remain available for reporting unless archived or deleted according to policy.

## 15.4 Public Event Page

**FR-PUB-001** Each published event shall have a unique public URL.

**FR-PUB-002** The public page shall display event name, date, time, organizer, and registration availability.

**FR-PUB-003** The public page shall display venue information when configured as visible.

**FR-PUB-004** The public page shall display event description when available.

**FR-PUB-005** The public page shall display available ticket/registration types.

**FR-PUB-006** The public page shall disable registration when the registration period has ended.

**FR-PUB-007** The public page shall disable registration when the event is cancelled.

## 15.5 Venue

**FR-VEN-001** An authorized user shall be able to associate venue information with an event.

**FR-VEN-002** Venue information shall support venue name, address, and notes.

**FR-VEN-003** The system shall allow an event to be marked as online, offline, or hybrid.

**FR-VEN-004** Venue visibility on the public event page shall be configurable.

## 15.6 Agenda / Sessions

**FR-AGD-001** An authorized user shall be able to create agenda items for an event.

**FR-AGD-002** Each agenda item shall contain a title and scheduled start time.

**FR-AGD-003** An agenda item may contain end time, location, speaker text, and description.

**FR-AGD-004** Agenda items shall be displayed chronologically by default.

**FR-AGD-005** An authorized user shall be able to reorder agenda items when two or more items share overlapping or flexible scheduling.

## 15.7 Registration Configuration

**FR-REG-001** An authorized user shall be able to enable or disable registration for an event.

**FR-REG-002** An authorized user shall be able to define registration start and end dates.

**FR-REG-003** The system shall prevent public registration before the configured registration start time.

**FR-REG-004** The system shall prevent public registration after the configured registration end time.

**FR-REG-005** The system shall allow registration to close automatically when event or ticket capacity is reached.

**FR-REG-006** The default registration fields shall include name and email.

**FR-REG-007** Phone and organization shall be configurable as optional or required fields.

## 15.8 Custom Registration Fields

**FR-CUSTOM-001** An authorized user shall be able to add custom registration fields.

**FR-CUSTOM-002** MVP custom field types shall include text, textarea, select, radio, checkbox, and date.

**FR-CUSTOM-003** A custom field shall be configurable as required or optional.

**FR-CUSTOM-004** The system shall reject submission when a required custom field is empty.

**FR-CUSTOM-005** Custom field responses shall be associated with the corresponding registration.

## 15.9 Ticket Types

**FR-TYPE-001** An authorized user shall be able to create one or more ticket types for an event.

**FR-TYPE-002** A ticket type shall contain a name.

**FR-TYPE-003** A ticket type may contain a price, capacity, description, availability start time, and availability end time.

**FR-TYPE-004** The MVP shall support free and informational-price ticket types even if online payment is not enabled.

**FR-TYPE-005** The system shall prevent new registrations for a ticket type whose capacity is exhausted.

**FR-TYPE-006** The system shall prevent new registrations outside the ticket type availability period.

## 15.10 Registration Submission

**FR-SUB-001** An attendee shall be able to submit a registration for a published event while registration is open.

**FR-SUB-002** The system shall validate all required registration fields.

**FR-SUB-003** The system shall associate the registration with the selected ticket type when ticket selection is required.

**FR-SUB-004** The system shall generate a unique registration identifier after successful submission.

**FR-SUB-005** The system shall prevent capacity from being exceeded by successful registrations.

**FR-SUB-006** The system shall display a confirmation result after successful registration.

**FR-SUB-007** The system shall not create a successful registration when required validation fails.

**FR-SUB-008** The system shall record the registration timestamp.

## 15.11 Attendee Management

**FR-ATT-001** Authorized users shall be able to view attendees for accessible events.

**FR-ATT-002** The attendee list shall display at minimum attendee name, email, ticket type, registration status, and check-in status.

**FR-ATT-003** Authorized users shall be able to search attendees by name, email, or registration identifier.

**FR-ATT-004** Authorized users shall be able to filter attendees by registration status, ticket type, and check-in status.

**FR-ATT-005** An authorized user shall be able to view attendee registration details.

**FR-ATT-006** An authorized user shall be able to change supported registration statuses according to business rules.

## 15.12 Tickets

**FR-TKT-001** Each confirmed registration that requires a ticket shall receive a unique ticket identifier.

**FR-TKT-002** Each ticket shall contain a QR code representing a unique ticket token or identifier.

**FR-TKT-003** Two distinct tickets shall not share the same unique ticket identifier.

**FR-TKT-004** An attendee shall be able to access ticket information through the registration confirmation flow.

**FR-TKT-005** An authorized user shall be able to view the ticket associated with a registration.

## 15.13 Check-In

**FR-CHK-001** Authorized Staff, Event Manager, Admin, or Owner users shall be able to perform check-in for permitted events.

**FR-CHK-002** The system shall support manual attendee check-in from an attendee detail or search result.

**FR-CHK-003** The system shall support QR-based check-in through a web-accessible scanner interface when the device supports camera access.

**FR-CHK-004** The system shall validate the ticket before recording check-in.

**FR-CHK-005** A successful check-in shall record attendee, event, time, and operator.

**FR-CHK-006** The system shall detect a ticket that has already been checked in.

**FR-CHK-007** A duplicate check-in attempt shall not silently create a second attendance record.

**FR-CHK-008** A duplicate check-in attempt shall display the previous check-in status to the operator.

**FR-CHK-009** Invalid or unknown QR codes shall not create a check-in record.

**FR-CHK-010** Check-in totals shall update on the event dashboard after successful check-in.

## 15.14 Dashboard

**FR-DSH-001** An authenticated authorized user shall be able to view an organization dashboard.

**FR-DSH-002** The organization dashboard shall display event counts by relevant status.

**FR-DSH-003** The event dashboard shall display total registrations.

**FR-DSH-004** The event dashboard shall display total checked-in attendees.

**FR-DSH-005** The event dashboard shall display attendance percentage when at least one confirmed attendee exists.

**FR-DSH-006** Dashboard data shall respect the user's access permissions.

## 15.15 Notifications

**FR-NOT-001** The system shall support registration-confirmation email.

**FR-NOT-002** Registration-confirmation email shall contain the event name and attendee registration reference.

**FR-NOT-003** When ticketing is enabled, registration-confirmation communication shall provide ticket access or ticket information.

**FR-NOT-004** Authorized users shall be able to trigger or schedule supported event reminder notifications in future phases; the MVP shall support at least one configurable reminder event if enabled.

**FR-NOT-005** Notification failures shall not delete or invalidate the registration.

## 15.16 Reports

**FR-RPT-001** Authorized users shall be able to view a registration summary.

**FR-RPT-002** Authorized users shall be able to view attendance summary.

**FR-RPT-003** Registration reports shall include registration totals by ticket type.

**FR-RPT-004** Attendance reports shall include total confirmed attendees, total checked in, and attendance percentage.

**FR-RPT-005** Reports shall respect event and organization access permissions.

## 15.17 Export

**FR-EXP-001** Authorized users shall be able to export attendee data for an accessible event.

**FR-EXP-002** Exported data shall include only fields the user is authorized to view.

**FR-EXP-003** The MVP shall provide a CSV export compatible with common spreadsheet software.

**FR-EXP-004** Export shall support currently applied attendee filters when the user explicitly chooses filtered export.

## 15.18 Settings

**FR-SET-001** Authorized organization users shall be able to configure organization name and basic branding information.

**FR-SET-002** Authorized users shall be able to configure default timezone.

**FR-SET-003** Authorized users shall be able to configure supported locale when localization is enabled.

**FR-SET-004** Changes to restricted organization settings shall be auditable.

---

# 16. Non-Functional Requirements

## 16.1 Usability

**NFR-US-001** Core organizer workflows shall be usable from modern desktop browsers.

**NFR-US-002** Public registration and check-in interfaces shall be responsive on modern mobile browsers.

**NFR-US-003** Core actions shall provide visible success or failure feedback.

**NFR-US-004** Destructive actions shall require explicit confirmation.

## 16.2 Availability

**NFR-AV-001** Product behavior shall not depend on third-party SaaS services for basic event creation, registration management, attendee management, and reporting.

## 16.3 Accessibility

**NFR-ACC-001** Interactive form fields shall have visible labels or accessible names.

**NFR-ACC-002** Validation errors shall be associated with the relevant fields.

**NFR-ACC-003** Primary workflows shall be operable by keyboard where practical for web interfaces.

## 16.4 Maintainability

**NFR-MNT-001** Product modules shall have clearly defined responsibilities.

**NFR-MNT-002** Product documentation shall define feature ownership and expected behavior before commercial release.

## 16.5 Compatibility

**NFR-CMP-001** The product shall support current stable versions of major Chromium-based browsers and Firefox.

**NFR-CMP-002** Public attendee pages shall remain usable on common mobile viewport sizes.

---

# 17. User Stories

## 17.1 Organizer

**US-001** As an Event Manager, I want to create an event so that I can centralize its operational information.

**US-002** As an Event Manager, I want to publish an event page so that participants can access event information.

**US-003** As an Event Manager, I want to configure registration dates so that registration opens and closes automatically.

**US-004** As an Event Manager, I want to define ticket types so that participants can register using the correct category.

**US-005** As an Event Manager, I want to view attendee status so that I know registration progress.

**US-006** As an Event Manager, I want attendance statistics so that I can monitor event turnout.

**US-007** As an Owner, I want a dashboard of organization events so that I can monitor operations without requesting manual reports.

## 17.2 Staff

**US-008** As Event Staff, I want to search an attendee so that I can verify registration at the venue.

**US-009** As Event Staff, I want to scan a ticket QR code so that I can check in attendees quickly.

**US-010** As Event Staff, I want duplicate-ticket warnings so that the same ticket is not accepted twice without notice.

## 17.3 Attendee

**US-011** As an Attendee, I want to view event details before registering.

**US-012** As an Attendee, I want to register without creating an account so that the registration process is fast.

**US-013** As an Attendee, I want a confirmation after registration so that I know my registration was received.

**US-014** As an Attendee, I want a unique ticket or QR code so that I can be verified at the event.

## 17.4 Management

**US-015** As a Viewer, I want read-only access to event performance so that I can monitor progress without modifying data.

---

# 18. User Journeys

## 18.1 Organizer Event Creation Journey

```text
Login
→ Select Organization
→ Create Event
→ Enter Event Details
→ Configure Venue
→ Add Agenda
→ Configure Ticket Types
→ Configure Registration
→ Preview
→ Publish
```

Acceptance outcome:

- Event is publicly accessible only after successful publication.
- Required publish fields must be validated before publication.

## 18.2 Attendee Registration Journey

```text
Open Public Event Page
→ Review Event Information
→ Select Ticket Type
→ Complete Registration Form
→ Submit
→ Receive Confirmation
→ Access Ticket / QR
```

Acceptance outcome:

- Registration is stored once.
- Ticket capacity is not exceeded.
- Confirmation is visible immediately after success.

## 18.3 Event-Day Check-In Journey

```text
Staff Login
→ Open Assigned Event
→ Open Check-In
→ Scan QR / Search Attendee
→ Validate
→ Confirm Check-In
→ Display Result
```

Acceptance outcome:

- Valid ticket creates attendance record.
- Duplicate ticket displays duplicate warning.
- Invalid ticket creates no attendance record.

## 18.4 Post-Event Reporting Journey

```text
Event Manager Login
→ Open Completed Event
→ View Dashboard
→ Open Report
→ Filter if Needed
→ Export
```

Acceptance outcome:

- Report totals match current system data.
- Export reflects selected event and permitted fields.

---

# 19. Business Rules

**BR-001** Every event shall belong to one organization.

**BR-002** Every organization shall have at least one Owner while the organization is active.

**BR-003** Only authorized organization members may access private organization data.

**BR-004** Draft events shall not accept public registrations.

**BR-005** Cancelled events shall not accept new public registrations.

**BR-006** Registration may only occur while registration is open.

**BR-007** A ticket type with exhausted capacity shall not accept new successful registrations.

**BR-008** Registration capacity shall not exceed configured event or ticket limits.

**BR-009** Every ticket shall be associated with one registration.

**BR-010** Every ticket identifier shall be unique.

**BR-011** A successful check-in shall be associated with exactly one attendee registration.

**BR-012** Duplicate check-in attempts shall not be silently counted as additional attendance.

**BR-013** Attendee registration data shall remain available for reporting until deleted according to retention policy.

**BR-014** Role permissions shall be evaluated against both role and organization/event access.

**BR-015** A user shall not gain access to another organization merely by knowing its URL or record identifier.

**BR-016** Event status transitions that materially affect public access or registration shall be auditable.

**BR-017** Removing a member from an organization shall revoke future organization access.

**BR-018** Historical audit data shall identify the actor known at the time of the action even if the actor later loses access.

**BR-019** Event reports shall use system records as their authoritative source.

**BR-020** Public registration shall not expose internal administrative notes.

---

# 20. Permissions Matrix

Legend:

- **Full** = create, read, update, and supported delete/archive actions
- **Manage** = operational management within allowed scope
- **Read** = read-only
- **Limited** = only specific operational actions
- **None** = no access

| Capability | Owner | Admin | Event Manager | Staff | Viewer | Attendee |
|---|---|---|---|---|---|---|
| Organization settings | Full | Manage | None | None | Read basic | None |
| Organization members | Full | Manage | Read assigned | None | None | None |
| Create event | Full | Full | Allowed | None | None | None |
| Edit assigned event | Full | Full | Manage | None | None | None |
| Publish/cancel event | Full | Full | Manage | None | None | None |
| Delete/archive event | Full | Manage | Limited | None | None | None |
| Venue | Full | Full | Manage | Read | Read | Public only |
| Agenda | Full | Full | Manage | Read | Read | Public only |
| Ticket types | Full | Full | Manage | Read | Read | Select public |
| Registration settings | Full | Full | Manage | None | Read | None |
| Attendee list | Full | Full | Manage | Limited | Read | Own confirmation only |
| Attendee edit/status | Full | Full | Manage | Limited | None | None |
| Check-in | Full | Full | Full | Allowed | None | None |
| Reports | Full | Full | Read/Manage | Limited | Read | None |
| Export | Full | Full | Allowed | Limited/No | Configurable Read | None |
| Activity logs | Full | Full | Read assigned | None | None | None |

Permission behavior shall be testable by attempting each restricted action with each role.

---

# 21. Main Modules

## 21.1 Core Platform Modules

- Authentication
- Users
- Organizations
- Memberships
- Roles
- Settings
- Notifications
- File management
- Activity logs

## 21.2 Event Domain Modules

- Events
- Venues
- Agenda
- Registration
- Custom registration fields
- Attendees
- Ticket types
- Tickets
- Check-in
- Reports

## 21.3 Module Relationship

```text
Organization
    ├── Members
    ├── Settings
    └── Events
          ├── Venue
          ├── Agenda
          ├── Ticket Types
          ├── Registration Configuration
          ├── Registrations
          │     ├── Attendee Data
          │     └── Ticket
          ├── Check-Ins
          └── Reports
```

---

# 22. Dashboard Requirements

## 22.1 Organization Dashboard

The organization dashboard shall display:

- Total active events
- Upcoming events
- Ongoing events
- Completed events
- Recent events
- Registration totals across accessible events where practical

## 22.2 Event Dashboard

The event dashboard shall display:

- Event status
- Registration status
- Registration start/end
- Event capacity where configured
- Total registrations
- Total confirmed registrations
- Total checked-in attendees
- Attendance percentage
- Ticket-type summary
- Recent registrations
- Recent check-ins

## 22.3 Dashboard Rules

**DASH-001** Dashboard data shall only include records the current user may access.

**DASH-002** Attendance percentage shall be calculated from eligible confirmed attendees, not raw abandoned or invalid records.

**DASH-003** When no eligible attendee exists, attendance percentage shall display as `0%` or `Not available` rather than producing an error.

**DASH-004** Dashboard totals shall match corresponding report totals for the same filters.

---

# 23. Reporting Requirements

## 23.1 Required MVP Reports

1. Event summary report
2. Registration report
3. Ticket type report
4. Attendance report

## 23.2 Event Summary Report

Shall include:

- Event name
- Date
- Status
- Venue
- Total registrations
- Total checked in
- Attendance percentage

## 23.3 Registration Report

Shall support:

- Total registrations
- Registration status
- Ticket type
- Registration date
- Attendee identity fields

## 23.4 Attendance Report

Shall support:

- Attendee
- Registration identifier
- Ticket type
- Check-in status
- Check-in timestamp
- Check-in operator where permitted

## 23.5 Testability

A report shall be considered correct when its totals reconcile with the filtered underlying records for the same event.

---

# 24. Notification Requirements

## 24.1 MVP Notification Events

The MVP shall support notification behavior for:

- Successful registration confirmation
- Ticket availability/confirmation
- Password reset
- Organization invitation if invitations are implemented
- Event reminder if enabled

## 24.2 Notification Rules

**NOT-001** A failed email delivery shall not roll back an otherwise successful registration.

**NOT-002** Notification content shall not expose private internal notes.

**NOT-003** Event-related notification content shall identify the event.

**NOT-004** Ticket-related notification content shall not expose another attendee's ticket.

**NOT-005** Notification delivery status should be available for administrative troubleshooting where feasible.

---

# 25. Search and Filtering Requirements

## 25.1 Event Search

Authorized users shall be able to search events by:

- Event name
- Status
- Date range

## 25.2 Attendee Search

Authorized users shall be able to search attendees by:

- Name
- Email
- Registration identifier
- Ticket identifier where permitted

## 25.3 Attendee Filters

The system shall support filtering by:

- Registration status
- Ticket type
- Check-in status
- Registration date range

## 25.4 Search Behavior

**SRCH-001** Search shall not expose inaccessible organization records.

**SRCH-002** Empty search results shall display a clear no-results state.

**SRCH-003** Clearing filters shall restore the default list state.

**SRCH-004** Applying filters shall not modify underlying records.

---

# 26. Import/Export Requirements

## 26.1 Export

MVP shall support:

- Attendee CSV export
- Registration CSV export
- Attendance CSV export

## 26.2 Import

Bulk import is optional for the first MVP release and may be deferred.

If attendee import is included:

**IMP-001** The system shall provide a documented import template.

**IMP-002** Invalid rows shall be identified to the user.

**IMP-003** The system shall not silently discard invalid rows.

**IMP-004** Import shall not bypass required organization/event access controls.

## 26.3 Export Rules

**EXP-001** Exports shall include the event identifier or event name.

**EXP-002** Exported timestamps shall use the organization's configured timezone or clearly documented timezone.

**EXP-003** Sensitive internal-only fields shall not be included unless explicitly authorized.

---

# 27. File Upload Requirements

The MVP may allow uploads for:

- Event banner
- Organization logo
- Supporting event image

## 27.1 Rules

**FILE-001** The system shall reject unsupported file types.

**FILE-002** The system shall reject files exceeding the configured maximum upload size.

**FILE-003** Uploaded files shall be associated with the owning organization or event.

**FILE-004** Users without access to the owning organization shall not access private files through application-level navigation.

**FILE-005** Replacing an event banner shall update the event display without creating duplicate active-banner references.

**FILE-006** Upload errors shall provide a user-readable message.

---

# 28. Audit Trail Requirements

The system shall create audit records for material administrative actions.

## 28.1 Minimum Audited Actions

- Event created
- Event updated
- Event published
- Event cancelled
- Event archived
- Organization settings changed
- Member role changed
- Member removed
- Registration status changed manually
- Manual check-in
- QR check-in
- Check-in reversed if reversal is supported
- Ticket or critical attendee state changed

## 28.2 Audit Entry Fields

Each audit entry shall contain:

- Action identifier/type
- Timestamp
- Actor
- Organization
- Related entity type
- Related entity identifier
- Human-readable action summary where supported

## 28.3 Audit Rules

**AUD-001** Ordinary users shall not be able to edit audit entries.

**AUD-002** Audit records shall be ordered by newest first by default.

**AUD-003** Audit records shall respect organization access.

---

# 29. Security Requirements

## 29.1 Authentication Security

**SEC-001** Passwords shall never be displayed in plain text after submission.

**SEC-002** Password-reset tokens shall expire.

**SEC-003** Logout shall terminate the active authenticated session as expected by the product.

## 29.2 Authorization

**SEC-004** Every protected organization action shall require authorization.

**SEC-005** Event access shall be restricted by organization membership and role.

**SEC-006** Changing a URL identifier shall not grant access to another organization's data.

## 29.3 Public Registration

**SEC-007** Public registration forms shall validate expected input types and required fields.

**SEC-008** Public registration shall not expose administrative-only fields.

**SEC-009** Ticket identifiers shall not be sequentially guessable in a way that permits unauthorized ticket access.

## 29.4 File Security

**SEC-010** Private files shall not be exposed merely through predictable file names.

## 29.5 Audit and Abuse

**SEC-011** Failed authorization attempts shall return a controlled error response.

**SEC-012** Sensitive administrative actions shall be auditable where defined in Section 28.

---

# 30. Localization Requirements

## 30.1 MVP

The product shall be localization-ready.

MVP language support may begin with one default language, but user-facing text shall be structured so additional languages can be introduced without redesigning product workflows.

## 30.2 Required Localization Behaviors

**LOC-001** Organization or application configuration shall support a default locale setting when multiple locales are enabled.

**LOC-002** Date/time presentation shall respect configured timezone.

**LOC-003** User-facing event dates shall not be stored or displayed ambiguously.

**LOC-004** Currency values, when shown, shall identify the currency.

## 30.3 Future Language Targets

Potential future languages:

- Indonesian
- English

---

# 31. Performance Requirements

These targets apply under normal hosting conditions and representative production data.

**PERF-001** Standard authenticated page responses should complete within 3 seconds for typical organization data volumes.

**PERF-002** Attendee search should return within 2 seconds for events up to 10,000 attendees under normal operating conditions.

**PERF-003** QR validation and check-in result should return within 3 seconds under normal operating conditions.

**PERF-004** Dashboard loading should complete within 4 seconds for an organization with up to 100 events and 100,000 total historical registrations under normal operating conditions.

**PERF-005** CSV export for 10,000 attendee rows shall complete without browser memory failure.

**PERF-006** Public registration submission shall prevent accidental duplicate creation caused by repeated rapid submission where reasonably detectable.

---

# 32. Data Retention Requirements

## 32.1 Default Retention

Event and registration records shall remain available after event completion unless explicitly deleted or archived according to organization policy.

## 32.2 Deletion

**RET-001** Deleting or anonymizing attendee data shall require an authorized action.

**RET-002** Destructive actions affecting attendee data shall require explicit confirmation.

**RET-003** Archived events shall remain excluded from default active-event views but remain available to authorized users.

## 32.3 Future Policy Support

Future versions may support configurable retention periods by organization.

The MVP shall not implement automatic regulatory deletion schedules unless explicitly added to scope.

---

# 33. Error Handling Requirements

**ERR-001** Validation failures shall display user-readable messages.

**ERR-002** A failed operation shall not display raw technical stack traces to normal production users.

**ERR-003** Attempting to access a non-existent event shall display a controlled not-found state.

**ERR-004** Attempting to access an unauthorized organization/event shall display a controlled access-denied state.

**ERR-005** Failed file uploads shall explain whether the failure is caused by type, size, or another supported validation condition.

**ERR-006** Failed notification delivery shall not invalidate a successful registration.

**ERR-007** Invalid QR codes shall produce a clear invalid-ticket result.

**ERR-008** Already-used QR codes shall produce a clear already-checked-in result.

**ERR-009** Capacity conflicts during registration shall produce a clear registration-unavailable result.

**ERR-010** User-facing errors shall avoid exposing confidential system details.

---

# 34. Acceptance Criteria

The MVP shall be accepted only when all critical workflows below pass end-to-end testing.

## AC-01 Authentication

Given a registered user with valid credentials, when the user logs in, then the user shall reach an authorized application area.

## AC-02 Organization Isolation

Given two organizations, when a user belonging only to Organization A attempts to access Organization B private data, then access shall be denied.

## AC-03 Event Creation

Given an authorized Event Manager, when valid required event data is submitted, then one event shall be created under the selected organization.

## AC-04 Event Publication

Given an event missing required publish fields, when publication is attempted, then publication shall be rejected with validation feedback.

## AC-05 Public Event Page

Given a published event, when a public visitor opens its public URL, then public event information shall be displayed.

## AC-06 Closed Registration

Given a registration period that has ended, when a visitor attempts registration, then a successful registration shall not be created.

## AC-07 Capacity

Given a ticket type with zero remaining capacity, when a visitor attempts to register for it, then the registration shall be rejected.

## AC-08 Registration

Given valid registration data and available capacity, when the attendee submits the registration form, then one successful registration shall be created.

## AC-09 Ticket

Given a successful ticket-enabled registration, then the system shall create a unique ticket identifier and QR representation.

## AC-10 Attendee Search

Given an event with attendees, when an authorized user searches by exact attendee email, then the matching attendee shall be returned.

## AC-11 QR Check-In

Given a valid unused ticket, when an authorized operator scans it, then one successful check-in shall be recorded.

## AC-12 Duplicate Check-In

Given a ticket already checked in, when scanned again, then the system shall show the previous check-in state and shall not silently count a second attendance.

## AC-13 Invalid QR

Given an unknown QR token, when scanned, then no attendee shall be checked in.

## AC-14 Dashboard

Given confirmed attendees and check-in records, when the event dashboard is opened, then registration and attendance totals shall match report data.

## AC-15 Export

Given an accessible event, when an authorized user exports attendee data, then a readable CSV shall be generated containing authorized fields.

## AC-16 Audit Trail

Given an audited administrative action, when the action succeeds, then an audit record shall exist containing actor, action, related entity, and timestamp.

## AC-17 Permissions

Given a Staff user, when the user attempts an Owner-only setting change, then the change shall be rejected.

## AC-18 Mobile Registration

Given a common mobile viewport, when the attendee opens the public registration page, then the primary registration workflow shall be usable without horizontal scrolling for core form content.

---

# 35. Definition of Done

A feature is considered done only when:

1. Product behavior matches approved requirements.
2. Acceptance criteria are defined.
3. Acceptance criteria pass.
4. Permissions are validated for relevant roles.
5. Validation and error states are implemented in the product behavior.
6. Empty states are defined where applicable.
7. Responsive behavior is verified where applicable.
8. Audit requirements are implemented where applicable.
9. Notification behavior is verified where applicable.
10. No unresolved critical-severity defect remains.
11. User-facing copy is complete.
12. Product documentation is updated.
13. QA confirms expected behavior.
14. Existing critical workflows do not regress.
15. Commercially exposed settings use product-safe defaults.

A release is considered done only when all included features meet the above definition.

---

# 36. Product Risks

## 36.1 Scope Explosion

**Risk:** Event management can expand into CRM, payment, travel, streaming, sponsor, networking, mobile app, and marketing automation.

**Mitigation:** Protect the MVP golden path and require explicit product approval before adding non-MVP modules.

## 36.2 Permission Complexity

**Risk:** Too many roles and granular permissions can make the product difficult to understand.

**Mitigation:** Start with five authenticated default roles and expand only when validated.

## 36.3 Payment Complexity

**Risk:** Payment gateways introduce refunds, settlements, taxes, reconciliation, and gateway-specific behavior.

**Mitigation:** Keep online payment out of mandatory MVP scope.

## 36.4 Check-In Reliability

**Risk:** Event-day check-in is operationally critical.

**Mitigation:** Support both QR and manual attendee search/check-in.

## 36.5 Source-Code Upgrade Complexity

**Risk:** Customers may modify source code, making upgrades difficult.

**Mitigation:** Maintain clear product modules, configuration boundaries, release notes, and upgrade documentation.

## 36.6 SaaS Premature Complexity

**Risk:** Building full tenant isolation, metered billing, and enterprise provisioning too early increases complexity.

**Mitigation:** Use organization-based product concepts now and defer advanced SaaS mechanics.

## 36.7 Product Commoditization

**Risk:** Many event systems already exist.

**Mitigation:** Differentiate through simplicity, self-hosted ownership, source-code availability, white-label readiness, and focused event operations.

## 36.8 Free Tool Competition

**Risk:** Some customers consider spreadsheets and online forms sufficient.

**Mitigation:** Demonstrate the complete value chain: registration → ticket → check-in → attendance → report.

---

# 37. Future Development

Future product modules may include:

## 37.1 Professional Event Features

- Event duplication
- Promo codes
- Waiting lists
- Advanced registration fields
- Speaker management
- Certificate generation
- Certificate verification
- Surveys
- Custom email templates
- Advanced reports

## 37.2 Commerce

- Online payment
- Orders
- Invoices
- Refunds
- Coupons
- Financial reports

## 37.3 Event Operations

- Sponsor management
- Vendor management
- Team tasks
- Event documents
- Internal event timeline
- Budget tracking

## 37.4 Integration

- REST API
- Webhooks
- External CRM integration
- Messaging integration
- Calendar integration

## 37.5 SaaS

- Subscription plans
- Trials
- Usage limits
- Tenant administration
- Billing
- SaaS super-admin
- Plan-based features

## 37.6 White Label

- Custom logo
- Custom color/theme
- Custom email branding
- Custom domain
- Tenant branding
- Event microsite themes

## 37.7 Ecosystem

- Add-ons
- Extension modules
- Payment drivers
- Notification drivers
- Themes
- Commercial module marketplace

---

# 38. Version Roadmap

## Version 0.1 — Product Foundation

Goal: establish core product structure.

Scope:

- Authentication
- User profile
- Organization
- Organization members
- Basic roles
- Event CRUD
- Basic settings

Exit criteria:

- User can create and access an organization.
- Authorized user can create an event.
- Organization access isolation passes acceptance testing.

## Version 0.2 — Event Publishing

Scope:

- Event configuration
- Venue
- Agenda
- Public event page
- Publish workflow

Exit criteria:

- Event can move from Draft to Published.
- Published event has working public URL.

## Version 0.3 — Registration

Scope:

- Registration configuration
- Public registration form
- Custom fields
- Attendee records
- Registration status

Exit criteria:

- Public visitor can complete valid registration.
- Capacity and registration-period rules work.

## Version 0.4 — Ticketing

Scope:

- Ticket types
- Ticket generation
- Unique ticket identifiers
- QR code

Exit criteria:

- Successful ticket-enabled registration receives unique ticket.
- Duplicate ticket identifiers cannot occur.

## Version 0.5 — Event Operations

Scope:

- Attendee search
- Manual check-in
- QR check-in
- Duplicate detection
- Attendance status

Exit criteria:

- Staff can check in valid attendees.
- Invalid and duplicate scans are handled correctly.

## Version 0.6 — Dashboard and Reports

Scope:

- Organization dashboard
- Event dashboard
- Registration report
- Attendance report
- CSV export

Exit criteria:

- Dashboard and reports reconcile.
- Authorized user can export attendee data.

## Version 0.7 — Notifications and Audit

Scope:

- Registration confirmation
- Ticket notification
- Basic event reminder
- Activity/audit log

Exit criteria:

- Notification failure does not corrupt registration.
- Required audited actions create records.

## Version 0.8 — Product Hardening

Scope:

- Permission review
- Error-state review
- Performance review
- Mobile usability review
- Security review
- Localization readiness
- Documentation

Exit criteria:

- All MVP acceptance criteria pass.
- No critical defects remain.

## Version 1.0 — Commercial MVP

Commercial scope:

- Event management
- Registration
- Attendees
- Ticket types
- QR tickets
- Check-in
- Dashboard
- Reports
- Export
- Roles
- Notifications
- Audit trail
- Settings

Release objective:

> A commercially sellable EventFlow version capable of supporting real seminar, workshop, training, campus, company, community, conference, and organizational event workflows without requiring external spreadsheets for the core registration-to-attendance process.

## Version 1.x — Professional

Candidate features:

- Event clone
- Waiting list
- Promo code
- Certificate
- Survey
- Speaker module
- Advanced reports
- Custom email templates

## Version 2.x — Commerce and Integrations

Candidate features:

- Payment
- Orders
- Invoice
- Refund
- REST API
- Webhooks
- Messaging integration

## Version 3.x — SaaS and White Label

Candidate features:

- Subscription plans
- Usage limits
- SaaS administration
- Tenant billing
- Custom domain
- Advanced branding
- White-label controls

---

# Product Decision Summary

EventFlow shall be developed as a **focused event operations platform**, not as a complete enterprise event ecosystem in the MVP.

The commercial MVP shall optimize this primary lifecycle:

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

Features outside this lifecycle must demonstrate clear commercial value before entering MVP scope.

---

**End of Document**
