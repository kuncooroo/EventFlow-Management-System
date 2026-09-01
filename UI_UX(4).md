# EventFlow Management System — UI/UX Specification

**Document Path:** `docs/UI_UX.md`  
**Product:** EventFlow Management System  
**Document Type:** UI/UX Architecture & Product Design Specification  
**Authoring Role:** Senior SaaS Product Designer  
**Frontend Context:** Blade + Livewire + Alpine.js + Tailwind CSS  
**Authoritative Product Source:** `docs/PRD.md`  
**Architecture References:** `docs/SRS.md`, `docs/SYSTEM_DESIGN.md`, `docs/BUSINESS_FLOW.md`, `docs/DATABASE.md`  
**Document Version:** 1.0  
**Status:** Design Specification for MVP  

---

# Product Design Position

EventFlow shall look and behave like a **professional commercial SaaS product**, but it shall not imitate the visual density of enterprise event-management suites.

The interface shall optimize the primary product lifecycle:

```text
Create Event
→ Configure
→ Publish
→ Registration
→ Attendee Management
→ Ticket / QR
→ Check-In
→ Attendance
→ Report
```

The product has three primary UX surfaces:

```text
1. Authenticated SaaS Workspace
   Owner / Admin / Event Manager / Staff / Viewer

2. Event Operations Workspace
   Event-focused administration and event-day check-in

3. Public Attendee Experience
   Public event page / registration / confirmation / ticket
```

The MVP shall not create a dedicated authenticated Vendor portal because Vendor is not defined as an authenticated MVP role in the PRD. Vendor-specific product surfaces may be added later.

---

# 1. UX Principles

## 1.1 Workflow Before Features

Navigation and page design shall follow what users are trying to accomplish, not the internal database structure.

Primary organizer mental model:

```text
My Events
→ Open Event
→ Setup
→ Registrations
→ Attendees
→ Check-In
→ Reports
```

Avoid exposing technical module terminology to ordinary users.

---

## 1.2 One Clear Primary Action

Every page should have one obvious primary action.

Examples:

| Page | Primary Action |
|---|---|
| Events | Create Event |
| Event Draft | Continue Setup / Publish |
| Attendees | Add/Manage Attendee only if manual creation exists later |
| Registration | Configure Registration |
| Ticket Types | Add Ticket Type |
| Check-In | Scan QR |
| Reports | Export |
| Members | Invite Member |

Secondary actions shall not visually compete with the main task.

---

## 1.3 Progressive Disclosure

Show only the information required for the current task.

Example event creation:

```text
Step 1 — Event Basics
Step 2 — Venue & Schedule
Step 3 — Registration
Step 4 — Ticket Types
Step 5 — Review & Publish
```

Advanced settings should remain collapsed or placed on dedicated settings pages.

---

## 1.4 Status Must Always Be Understandable

Use text labels plus visual status treatment.

Never rely on color alone.

Examples:

- Draft
- Published
- Ongoing
- Completed
- Cancelled
- Archived

Registration:

- Confirmed
- Cancelled

Attendance:

- Checked In
- Not Checked In

---

## 1.5 Event Context Must Be Persistent

When working inside an event, the user should always understand:

- which event is open,
- current status,
- event date,
- registration state.

Event context should persist in:

- breadcrumb,
- event header,
- event sub-navigation.

---

## 1.6 Reduce Event-Day Friction

Check-in is a time-critical workflow.

The Check-In interface shall prioritize:

1. large scan action,
2. fast search fallback,
3. immediate validation feedback,
4. duplicate detection,
5. minimal navigation.

Do not place unrelated event management actions on the scanning screen.

---

## 1.7 Public Registration Must Feel Lightweight

Attendees shall not be required to understand the EventFlow administration product.

Public registration shall:

- avoid unnecessary navigation,
- avoid account creation in MVP,
- show clear event context,
- minimize form length,
- make confirmation unmistakable.

---

## 1.8 Safe by Default

High-risk actions must be visually distinct and confirmed.

Examples:

- Cancel Event
- Archive Event
- Remove Member
- Cancel Registration

Routine actions should not use disruptive confirmations.

---

## 1.9 Responsive by Use Case

Desktop is the primary environment for:

- configuration,
- reports,
- administration.

Mobile is especially important for:

- public registration,
- attendee ticket,
- QR scanning,
- attendee lookup,
- event-day operations.

---

## 1.10 Commercial Consistency

Every page shall feel part of the same product through:

- spacing,
- typography,
- control size,
- cards,
- tables,
- status badges,
- page titles,
- feedback patterns.

Avoid page-specific visual experimentation that breaks the SaaS system.

---

# 2. Application Layout

## 2.1 Authenticated Desktop Layout

Recommended desktop shell:

```text
┌───────────────────────────────────────────────────────────────┐
│ Sidebar │ Top Bar                                             │
│         ├─────────────────────────────────────────────────────┤
│         │ Breadcrumb                                          │
│         │ Page Title                         Primary Action    │
│         │ Supporting Description                               │
│         ├─────────────────────────────────────────────────────┤
│         │                                                     │
│         │ Main Content                                        │
│         │                                                     │
│         │                                                     │
└───────────────────────────────────────────────────────────────┘
```

## 2.2 Width Behavior

- Sidebar: fixed desktop width.
- Main content: fluid.
- Large content area shall use a readable maximum width for forms/settings.
- Tables and dashboard grids may use the full available content width.

## 2.3 Main Layout Regions

1. Global sidebar
2. Global top navigation
3. Breadcrumb/context
4. Page header
5. Optional event-level navigation
6. Content area
7. Toast layer
8. Modal/dialog layer

## 2.4 Page Header Pattern

Every major authenticated page should use:

```text
Breadcrumb

Page Title                              [Primary Action]
Short description

Optional tabs / event status
```

Example:

```text
Events / Digital Marketing Workshop

Attendees                              [Export CSV]
128 registered participants

[All] [Confirmed] [Cancelled] [Checked In]
```

---

# 3. Sidebar Structure

The sidebar is organization-level navigation.

## 3.1 Recommended Sidebar

```text
EVENTFLOW

[Organization Switcher]

Overview

EVENT MANAGEMENT
• Events

MANAGEMENT
• Members
• Reports

SYSTEM
• Settings

[User Profile]
```

## 3.2 MVP Sidebar Items

### Overview

Organization-level dashboard.

### Events

Primary entry to all event operations.

### Members

Visible according to role.

### Reports

Organization-level report entry where permitted.

### Settings

Organization configuration.

## 3.3 What Should Not Appear as Global Sidebar Modules

Do not create separate global sidebar links for:

- Venues
- Agenda
- Ticket Types
- Registration Fields
- Tickets
- Check-In

These belong inside an event context.

This prevents an overlong enterprise-style sidebar.

## 3.4 Role-Aware Sidebar

### Owner / Admin

```text
Overview
Events
Members
Reports
Settings
```

### Event Manager

```text
Overview
Events
Reports
```

Members/settings may be hidden or read-only according to permissions.

### Staff

```text
Overview
Events
```

Inside assigned event, Staff sees operational features such as Check-In.

### Viewer

```text
Overview
Events
Reports
```

All mutation actions hidden, but server authorization remains authoritative.

## 3.5 Sidebar States

Desktop:

- expanded by default,
- may collapse to icon rail if useful.

Tablet:

- off-canvas.

Mobile:

- replaced by mobile navigation/drawer.

---

# 4. Top Navigation

The top navigation handles global session/context tasks, not product-module navigation.

## 4.1 Top Bar Content

Recommended:

```text
[Mobile Menu]

Breadcrumb / Organization Context

                    [Optional Search] [Help] [User Menu]
```

## 4.2 Organization Switcher

When a user belongs to multiple organizations:

- current organization name visible,
- switcher provides only organizations the user may access.

Do not expose inaccessible organizations.

## 4.3 User Menu

Contains:

- Profile
- Account
- Logout

Future:

- language preference,
- notification preferences.

## 4.4 Global Search

Not mandatory for MVP.

If introduced, it should initially search:

- events,
- permitted attendees.

Do not create a visually prominent global search before there is a real cross-module search requirement.

---

# 5. Mobile Navigation

## 5.1 Authenticated Mobile Strategy

Use:

- compact top bar,
- slide-over navigation drawer,
- event sub-navigation as horizontal scrollable tabs or structured menu.

Do not compress the full desktop sidebar into tiny fixed icons.

## 5.2 Mobile Top Bar

```text
[☰] EventFlow / Event Name        [Profile]
```

## 5.3 Event-Day Shortcut

For Staff working on an event:

```text
Event
Attendees
Check-In
```

Check-In should be the most visually prominent event-day destination.

## 5.4 Public Mobile Navigation

Public event pages should use minimal navigation.

Recommended:

```text
Event Logo/Name
Event Information
Agenda
Register
```

On small screens, a sticky bottom CTA may show:

```text
[ Register Now ]
```

when registration is open.

---

# 6. Page Hierarchy

The information architecture shall use three levels.

## Level 1 — Organization

```text
Organization
├── Overview
├── Events
├── Members
├── Reports
└── Settings
```

## Level 2 — Event

```text
Event
├── Overview
├── Setup
├── Agenda
├── Registration
├── Ticket Types
├── Attendees
├── Check-In
├── Reports
└── Settings
```

## Level 3 — Entity Detail / Action

Examples:

```text
Attendees
└── Attendee Detail

Members
└── Member Detail

Event Setup
└── Edit Event

Registration
└── Custom Field Editor
```

Avoid more than three persistent navigation levels.

---

# 7. Sitemap

```text
/
├── Login
├── Forgot Password
├── Reset Password
│
├── Public Event
│   └── /e/{event-slug}
│       ├── Event Overview
│       ├── Agenda
│       ├── Registration
│       ├── Registration Confirmation
│       └── Ticket Access
│
└── App
    ├── Organization Selector
    │
    ├── Overview
    │
    ├── Events
    │   ├── Event List
    │   ├── Create Event
    │   │
    │   └── Event Detail
    │       ├── Overview
    │       ├── Setup
    │       │   ├── Event Details
    │       │   └── Venue
    │       ├── Agenda
    │       ├── Registration
    │       │   ├── Registration Settings
    │       │   └── Custom Fields
    │       ├── Ticket Types
    │       ├── Attendees
    │       │   └── Attendee Detail
    │       ├── Check-In
    │       ├── Reports
    │       └── Settings / Lifecycle
    │
    ├── Members
    │   ├── Member List
    │   └── Invite Member
    │
    ├── Reports
    │
    ├── Settings
    │   ├── General
    │   ├── Branding
    │   └── Localization
    │
    └── Profile
```

---

# 8. Dashboard Layout

## 8.1 Organization Dashboard

Goal:

> Help management understand event operations quickly.

Recommended structure:

```text
Welcome / Organization Name

[Active Events] [Upcoming] [Registrations] [Checked In]

Upcoming Events
┌─────────────────────────────────────────────┐
│ Event                     Date      Status  │
└─────────────────────────────────────────────┘

Recent Activity / Recent Events

Optional Summary
```

## 8.2 KPI Cards

Limit initial dashboard to high-signal metrics.

Recommended:

- Active Events
- Upcoming Events
- Total Registrations
- Total Checked-In

Avoid filling the dashboard with decorative metrics.

## 8.3 Event Dashboard

Recommended:

```text
Event Name                 Published
Date • Venue

[Registrations] [Confirmed] [Checked In] [Attendance %]

Registration Progress / Capacity

Ticket Types
General             75 / 100
Student             32 / 50

Recent Registrations

Recent Check-Ins

Setup Status / Quick Actions
```

## 8.4 Draft Event Dashboard

Draft events should emphasize readiness rather than zero-value analytics.

Example:

```text
Event Setup

✓ Event Details
✓ Venue
✓ Registration
! Ticket Types
✓ Agenda

[Preview Event] [Publish Event]
```

## 8.5 Dashboard Charts

Charts are optional in MVP.

Prefer:

- concise numeric cards,
- progress bar,
- simple trend only if useful.

Do not create charts solely to make the dashboard appear sophisticated.

---

# 9. Module Navigation

When an event is open, use event-level navigation separate from the organization sidebar.

## 9.1 Event Navigation

Recommended:

```text
Overview
Setup
Agenda
Registration
Ticket Types
Attendees
Check-In
Reports
```

`Settings` may be placed under Setup/Lifecycle to keep the tab count manageable.

## 9.2 Desktop

Use horizontal event tabs under the event header when space allows.

Alternative on narrower layouts:

- event secondary sidebar.

Do not use both simultaneously.

## 9.3 Mobile

Use:

- horizontally scrollable tabs,
- or event menu drawer.

Important operational items should appear first:

```text
Overview
Attendees
Check-In
```

for Staff.

## 9.4 Role-Aware Navigation

Navigation shall reflect permissions but must not be treated as security.

Staff example:

```text
Overview
Agenda
Attendees
Check-In
```

Viewer:

```text
Overview
Agenda
Attendees (read only if permitted)
Reports
```

---

# 10. Form Patterns

## 10.1 General Form Layout

Use a single-column form for most settings.

Recommended width:

```text
Readable form container
rather than full-screen width
```

## 10.2 Field Structure

Every field should contain:

```text
Label
Optional helper text
[ Input ]
Validation error
```

## 10.3 Required Fields

Use:

```text
Event Name *
```

Do not rely only on red color.

## 10.4 Form Sections

Large forms shall be broken into sections/cards.

Example Event Setup:

```text
Basic Information
Date & Time
Location
Contact
Registration Basics
```

## 10.5 Multi-Step Event Creation

Initial creation should not ask for every possible setting.

Recommended:

### Step 1
Event basics

### Step 2
Date/location

### Step 3
Registration

### Step 4
Review

After creation, detailed configuration happens in the Event Workspace.

## 10.6 Save Behavior

Use clear behavior:

- `Save Changes`
- `Save & Continue`
- `Cancel`

Avoid ambiguous buttons such as `Submit`.

## 10.7 Unsaved Changes

For multi-field forms, warn users before navigating away if meaningful unsaved changes exist.

Livewire interactions should not surprise users with hidden auto-save unless clearly communicated.

## 10.8 Custom Registration Field Builder

Use repeatable field cards.

Example:

```text
Full Name                  System field
Email                      System field
Phone                      Optional / Required

Custom Fields
┌─────────────────────────────────────┐
│ Institution                        │
│ Type: Text       Required: Yes     │
│                         Edit Delete │
└─────────────────────────────────────┘

[+ Add Field]
```

Avoid drag-and-drop as the only ordering mechanism; provide keyboard-accessible alternatives.

---

# 11. Table Patterns

Tables are central to a SaaS admin product.

## 11.1 Standard Table Anatomy

```text
Page Header

Search                    Filters        Export

┌───────────────────────────────────────────────┐
│ Name │ Status │ Type │ Date │ ... │ Actions │
├───────────────────────────────────────────────┤
│ ...                                           │
└───────────────────────────────────────────────┘

Pagination
```

## 11.2 Row Actions

Use:

- clickable primary identifier,
- compact overflow menu for secondary actions.

Avoid placing five icon buttons in every row.

## 11.3 Table Density

Default should be comfortable, not spreadsheet-dense.

Event-day attendee lookup may use slightly denser rows for speed.

## 11.4 Horizontal Overflow

On mobile:

- do not force large admin tables into unusable compressed columns.

Prefer:

1. hide non-critical columns,
2. convert rows to cards,
3. provide horizontal scroll only for data-heavy reports when unavoidable.

## 11.5 Bulk Actions

Do not add bulk actions before a validated requirement exists.

No bulk delete in MVP.

---

# 12. Filter Patterns

## 12.1 Event Filters

Recommended:

- Status
- Date range

## 12.2 Attendee Filters

Required:

- Registration Status
- Ticket Type
- Check-In Status
- Registration Date

## 12.3 Filter Layout

Desktop:

```text
Search [________________]

[Status ▼] [Ticket Type ▼] [Check-In ▼] [Date ▼] [Clear]
```

Mobile:

```text
Search
[Filters (3)]
```

opens a filter drawer/sheet.

## 12.4 Active Filter Visibility

Applied filters must be visible.

Example:

```text
Filters:
[Confirmed ×] [VIP ×] [Checked In ×]

Clear all
```

## 12.5 Filter Persistence

Within one list workflow, filters may persist during navigation back from a detail page.

Do not persist filters permanently across unrelated sessions unless intentionally designed.

---

# 13. Search Patterns

## 13.1 Search Scope

Search must indicate what it searches.

Good:

```text
Search attendee by name, email, or registration ID
```

Avoid:

```text
Search...
```

when scope is not obvious.

## 13.2 Debounce

Livewire search may use a short debounce for text search.

Do not issue a server request for every keystroke without need.

## 13.3 Attendee Search

Event-day search should prioritize:

1. exact registration/ticket code,
2. email,
3. name.

Result should clearly display:

- attendee name,
- ticket type,
- registration status,
- check-in status.

## 13.4 No Results

Use:

```text
No attendees found

Try another name, email, or registration ID.
```

Do not show empty table chrome only.

---

# 14. Modal Usage

Use modals sparingly.

## 14.1 Appropriate Modal Use

- Invite Member
- Add Ticket Type if form is small
- Add Registration Field
- Quick confirmation
- Quick attendee information
- Change role

## 14.2 Do Not Use Modal For

- full event creation,
- large event settings,
- complex reports,
- long attendee forms,
- multi-step registration configuration.

These need dedicated pages.

## 14.3 Modal Size

Use standard sizes:

- small confirmation,
- medium short form,
- large only when necessary.

## 14.4 Mobile Modal

On small screens, form modals may become full-height bottom sheets/pages.

---

# 15. Toast / Notification Behavior

## 15.1 Toast Purpose

Use toast for transient confirmation after actions that do not require further user decision.

Examples:

- Event saved.
- Ticket type created.
- Member invited.
- Settings updated.

## 15.2 Toast Types

- Success
- Warning
- Error
- Informational

## 15.3 Duration

Success/info:

- auto-dismiss after a reasonable interval.

Warning/error:

- remain longer or require explicit dismissal when important.

## 15.4 Do Not Use Toast For Critical Business Conflict

Examples:

- Ticket already checked in
- Event cannot be published
- Registration capacity exhausted

These need visible in-context messages.

## 15.5 Check-In Feedback

Check-in result should be a large, persistent state card.

Success:

```text
✓ Checked In

Rina Wulandari
General Ticket
10:42
```

Duplicate:

```text
! Already Checked In

Rina Wulandari
Previously checked in at 10:42
```

Invalid:

```text
× Invalid Ticket

This QR code cannot be used for this event.
```

Do not reduce these outcomes to small corner toasts.

---

# 16. Empty States

Every major list requires a designed empty state.

## 16.1 First Event

```text
No events yet

Create your first event to start managing
registration, attendees, and check-in.

[Create Event]
```

## 16.2 No Attendees

```text
No registrations yet

Share the public event link once your event
is published and registration is open.

[View Public Page]
```

## 16.3 No Agenda

```text
No agenda items yet

Add sessions or activities to build your event schedule.

[Add Agenda Item]
```

## 16.4 No Ticket Types

```text
No ticket types yet

Create a ticket type such as General, Student, or VIP.

[Add Ticket Type]
```

## 16.5 Empty State Rules

An empty state should explain:

1. what is missing,
2. why it matters,
3. what the user can do next.

Avoid decorative illustrations that dominate the page.

---

# 17. Loading States

## 17.1 Page Loading

Blade initial page load should use normal browser navigation.

Do not mimic SPA loading unnecessarily.

## 17.2 Livewire Loading

Use contextual states:

- button spinner,
- disabled submit,
- skeleton rows,
- inline progress state.

## 17.3 Button Loading

Example:

```text
[ Saving... ]
```

while preventing duplicate submission.

## 17.4 Table Loading

When search/filter changes:

- preserve table dimensions where possible,
- show skeleton/soft loading overlay,
- avoid flashing the entire page blank.

## 17.5 Check-In Loading

Scanning state:

```text
Validating ticket...
```

must be obvious and prevent accidental repeated operator actions.

---

# 18. Error States

## 18.1 Validation Error

Show near the field.

Example:

```text
Email *
[ invalid-email ]

Enter a valid email address.
```

## 18.2 Page-Level Error

Use for:

- access denied,
- not found,
- service temporarily unavailable.

## 18.3 Access Denied

```text
You don't have permission to access this page.

Return to Events
```

Do not explain internal authorization structure.

## 18.4 Event Not Found

```text
Event not found

The event may have been removed or the link is incorrect.
```

## 18.5 Business Conflict

Use contextual alert.

Example publish error:

```text
Event cannot be published yet

Complete the following:
• Event date
• Organizer name
• Registration configuration
```

## 18.6 Technical Error

Never expose:

- stack traces,
- SQL errors,
- file paths,
- environment configuration.

---

# 19. Confirmation Dialogs

## 19.1 Use Confirmation For

- Cancel Event
- Archive Event
- Remove Member
- Cancel Registration
- Delete uploaded asset where destructive
- Discard unsaved changes when necessary

## 19.2 Confirmation Structure

```text
Cancel this event?

New registrations will be disabled.
Existing registrations will remain available for reporting.

[Keep Event] [Cancel Event]
```

## 19.3 Dangerous Action Language

Buttons must describe the actual result.

Good:

- Cancel Event
- Remove Member
- Archive Event

Avoid:

- Yes
- OK
- Confirm

## 19.4 Typed Confirmation

Do not require typing an event name for ordinary MVP actions.

Reserve typed confirmation for future extremely destructive actions such as permanent data deletion.

---

# 20. Responsive Behavior

## 20.1 Breakpoint Philosophy

Use Tailwind responsive utilities, but design based on content behavior rather than treating breakpoints as separate products.

## 20.2 Desktop

Optimized for:

- dashboard,
- tables,
- event setup,
- reporting.

## 20.3 Tablet

- sidebar becomes collapsible/off-canvas,
- dashboard card grid reduces columns,
- forms remain single-column or two-column only where safe.

## 20.4 Mobile

Prioritize:

- public event page,
- registration,
- ticket,
- check-in,
- attendee lookup.

## 20.5 Mobile Table Conversion

Attendee card example:

```text
Rina Wulandari
rina@example.com

General Ticket
Confirmed • Checked In

[View]
```

## 20.6 Sticky Action

Public registration may use sticky bottom CTA.

Check-In may use sticky scan/manual-switch controls where useful.

## 20.7 Touch Targets

Interactive controls must be large enough for reliable touch use, especially event-day operation.

---

# 21. Accessibility Requirements

EventFlow shall target professional WCAG-aligned usability.

## 21.1 Semantic Structure

Use:

- proper heading order,
- real `<button>` for actions,
- real `<a>` for navigation,
- semantic forms,
- semantic tables.

## 21.2 Form Accessibility

Every input shall have:

- visible label or proper accessible name,
- programmatic error association,
- required-state indication.

## 21.3 Color

Status meaning must never rely on color alone.

Use text/icon plus color.

## 21.4 Contrast

Text, controls, and status treatments shall meet appropriate accessible contrast.

## 21.5 Focus

All keyboard-focusable elements require clear visible focus state.

## 21.6 Dialogs

Modal dialogs shall:

- move focus inside on open,
- trap focus while active,
- return focus to triggering control on close,
- support Escape where dismissal is safe.

## 21.7 Scanner Accessibility

Camera scanner must have manual attendee-search fallback.

A user must not be blocked solely because camera access is unavailable.

---

# 22. Keyboard Navigation

## 22.1 General

All major authenticated workflows should be operable without a mouse where practical.

## 22.2 Tab Order

Tab order follows visual reading order.

Do not use positive custom `tabindex` values to artificially reorder focus.

## 22.3 Keyboard Actions

- Enter submits simple forms when expected.
- Escape closes non-destructive modals.
- Arrow keys follow native component behavior.
- Dropdown controls support keyboard navigation.

## 22.4 Check-In

Operator should be able to:

1. focus attendee search,
2. type identifier/name,
3. move through results,
4. open attendee,
5. trigger manual check-in

using keyboard.

## 22.5 Custom Field Ordering

If drag-and-drop ordering exists later, provide move up/down controls for keyboard users.

---

# 23. Design Consistency Rules

## 23.1 Typography

Use one primary sans-serif interface typeface provided through the product's frontend configuration.

Typography hierarchy:

```text
Page title
Section title
Card title
Body
Secondary/meta text
Label
Caption
```

Avoid using many font weights and sizes.

## 23.2 Spacing

Use Tailwind spacing scale consistently.

Avoid arbitrary spacing values unless required.

## 23.3 Corner Radius

Use a small standardized radius family.

Do not mix:

- sharp cards,
- highly rounded pills,
- excessive bubble UI.

Status badges may use pill treatment.

## 23.4 Shadows

Use subtle elevation only for:

- overlays,
- dropdowns,
- modal,
- selected floating surfaces.

Do not put strong shadows on every card.

## 23.5 Icons

Use one icon library consistently if an icon library is selected.

Icons supplement labels; they do not replace important text.

## 23.6 Buttons

Standard hierarchy:

### Primary
Main page action.

### Secondary
Alternative action.

### Tertiary / Ghost
Low-emphasis action.

### Danger
Destructive action.

Only one visually dominant primary action should exist in a local context.

## 23.7 Status Badges

Use consistent component for all statuses.

Example:

```text
[Draft]
[Published]
[Ongoing]
[Completed]
[Cancelled]
[Archived]
```

## 23.8 Cards

Cards should group meaningful content.

Do not place every form field inside its own card.

## 23.9 Design Tokens

At implementation time, define reusable Tailwind-level design tokens/component conventions for:

- background,
- surface,
- border,
- primary text,
- secondary text,
- brand accent,
- success,
- warning,
- danger,
- focus ring.

Exact brand colors are intentionally not fixed by this document.

---

# 24. CRUD Page Standards

## 24.1 List Page

Required pattern:

```text
Breadcrumb
Title + Description                  Primary Action

Search / Filters / Secondary Actions

Table or Responsive Cards

Pagination
```

## 24.2 Create Page

```text
Breadcrumb
Create {Entity}

Form sections

[Cancel] [Create {Entity}]
```

## 24.3 Edit Page

```text
Breadcrumb
Edit {Entity}

Form

[Cancel] [Save Changes]
```

Dangerous lifecycle actions shall not sit next to Save as equal visual actions.

## 24.4 Delete Behavior

Most EventFlow domain objects use:

- archive,
- cancel,
- deactivate,

rather than hard delete.

CRUD UI must use domain language.

Examples:

- Archive Event
- Cancel Registration
- Deactivate Ticket Type

not generic `Delete`.

## 24.5 Success Return

After create:

- navigate to new detail/setup page.

After edit:

- remain on useful context and show success feedback.

---

# 25. Detail Page Standards

## 25.1 Header

Entity detail pages shall show:

- title,
- key status,
- short metadata,
- primary action.

Event example:

```text
Digital Marketing Workshop      [Published]

12 September 2026 • Grand Hall
Registration open

[View Public Page]
```

## 25.2 Event Detail Composition

```text
Event Header
Event Navigation

Overview Metrics
Operational Alerts
Recent Activity
Quick Actions
```

## 25.3 Attendee Detail

Recommended:

```text
Rina Wulandari                    Confirmed
rina@example.com

Registration
• ID
• Ticket Type
• Registered At

Attendance
• Checked In / Not Checked In
• Time
• Operator

Registration Answers

Ticket
[View Ticket]

Administrative Actions
[Cancel Registration]
```

## 25.4 Information Hierarchy

Primary business data appears first.

Audit/system metadata should not dominate the detail screen.

---

# 26. Settings Pages

## 26.1 Settings Structure

Recommended organization settings:

```text
Settings
├── General
├── Branding
└── Localization
```

Future:

```text
Notifications
Domains
Billing
Integrations
```

but these shall not appear as empty placeholders in MVP.

## 26.2 General

- Organization Name
- Default Currency if configured

## 26.3 Branding

- Organization Logo
- Basic branding preview

White-label controls are future functionality; do not imply full theme customization in MVP.

## 26.4 Localization

- Timezone
- Locale when multiple languages are enabled

## 26.5 Settings Save Pattern

Each logical section may have its own Save action.

Avoid one giant Save button for unrelated settings pages.

---

# 27. Profile Pages

## 27.1 Profile Content

Authenticated user profile:

- Name
- Email
- Basic account information

Future:

- password change,
- language preference,
- security settings.

## 27.2 Organization Role

Display organization membership context but do not allow arbitrary self-role editing.

Example:

```text
Current Organization
EventFlow Demo Organization

Role
Event Manager
```

## 27.3 Account vs Organization Settings

Clearly separate:

```text
Profile = me
Organization Settings = workspace
```

This prevents users from confusing personal and tenant settings.

---

# 28. Authentication Pages

## 28.1 Layout

Authentication pages should be simple and brand-consistent.

Desktop:

```text
┌──────────────────────────────────────────┐
│ EventFlow                                │
│                                          │
│ Welcome back                             │
│ Sign in to manage your events.           │
│                                          │
│ Email                                    │
│ [____________________________]           │
│ Password                                 │
│ [____________________________]           │
│                                          │
│ [ Sign In ]                              │
│                                          │
│ Forgot password?                         │
└──────────────────────────────────────────┘
```

## 28.2 Login

Required:

- Email
- Password
- Sign In
- Forgot Password

Do not display public organizer account sign-up unless the PRD is later expanded to support it.

## 28.3 Forgot Password

One field:

- Email

Use privacy-safe success copy regardless of account discovery details where appropriate.

## 28.4 Reset Password

- Email/context where framework flow requires
- New Password
- Confirm Password
- Reset Password

## 28.5 Authentication UX Rules

- no unnecessary marketing carousel,
- no decorative animation blocking form access,
- clear focus states,
- visible errors,
- responsive mobile layout.

---

# 29. Public Pages

The public attendee experience must be visually related to EventFlow but significantly simpler than the admin SaaS.

## 29.1 Public Event Page

Recommended layout:

```text
Event Banner

Event Name
Date / Time
Venue / Online
Organizer

[Register Now]

About Event

Agenda

Ticket Types

Registration Information

Organizer Contact
```

## 29.2 Event Status Behavior

### Published + Registration Open

Show prominent:

```text
[Register Now]
```

### Registration Scheduled

Show:

```text
Registration opens on ...
```

### Registration Closed

Show:

```text
Registration Closed
```

### Sold Out

Show:

```text
Sold Out
```

### Cancelled

Prominent alert:

```text
This event has been cancelled.
```

Do not show active registration CTA.

## 29.3 Registration Page

Recommended progression:

```text
Event Summary

Choose Ticket Type

Your Information

Custom Questions

Review

[Complete Registration]
```

For simple events, these may remain on a single page instead of creating a multi-page wizard.

## 29.4 Registration Confirmation

Must clearly indicate success.

```text
✓ Registration Confirmed

You're registered for
Digital Marketing Workshop

Registration ID
01K...

[View Ticket]

A confirmation has been sent to
rina@example.com
```

If email delivery later fails, the on-screen confirmation remains authoritative.

## 29.5 Ticket Page

Mobile-first design:

```text
Event Name

Rina Wulandari
General Ticket

┌───────────────┐
│               │
│    QR CODE    │
│               │
└───────────────┘

Ticket ID
01K...

Event Date
Venue
```

QR should be visually large enough for fast event-day scanning.

## 29.6 Public Page Accessibility

- semantic headings,
- readable contrast,
- no admin terminology,
- accessible form labels,
- clear validation,
- no mandatory account creation.

---

# 30. Demo Mode Considerations

Demo mode is important for a commercial source-code/SaaS product because prospective buyers may need to explore the product safely.

Demo mode is **not an MVP business requirement**, but the UI architecture should allow it later.

## 30.1 Demo Goals

A public commercial demo should help users experience:

- dashboard,
- events,
- attendee list,
- reporting,
- check-in UI,
- settings preview,

without damaging the shared demo environment.

## 30.2 Demo Restrictions

When demo mode is enabled, restrict high-risk actions such as:

- changing credentials,
- removing demo Owner,
- permanently deleting data,
- changing mail credentials,
- changing storage/infrastructure settings.

## 30.3 Demo Banner

Always show visible non-intrusive context:

```text
Demo Mode
Changes may be reset periodically.
```

## 30.4 Demo Data

Use realistic but synthetic data.

Example events:

- Technology Conference 2026
- Digital Marketing Workshop
- Campus Career Fair

Do not use actual customer or attendee data.

## 30.5 Destructive Actions

Options:

1. disable destructive actions, or
2. allow them within isolated demo organization and automatically reset demo data.

For a shared public demo, disabling dangerous system-level actions is simpler.

## 30.6 Demo Reset

Future commercial implementation may restore seeded demo state periodically.

The UI should not promise permanent persistence in demo mode.

---

# 31. Event Lifecycle UX

Although not separately requested, lifecycle design is central to EventFlow.

## 31.1 Draft

Primary UI goal:

> Complete setup and publish.

Use setup checklist.

## 31.2 Published

Primary UI goal:

> Monitor registration and prepare operations.

Show:

- public page link,
- registration status,
- attendees,
- ticket capacity.

## 31.3 Ongoing

Primary UI goal:

> Operate event.

Reorder event navigation emphasis:

```text
Overview
Attendees
Check-In
Agenda
Reports
```

## 31.4 Completed

Primary UI goal:

> Review results.

Emphasize:

- attendance,
- report,
- export.

## 31.5 Cancelled

Primary UI goal:

> Preserve history and make cancellation unmistakable.

Show a prominent cancellation banner.

## 31.6 Archived

Primary UI goal:

> Historical read-oriented access.

Mutation actions should be heavily reduced.

---

# 32. Event Creation UX

## 32.1 Create Event Minimum

The initial creation should ask only:

- Event Name
- Start Date/Time
- End Date/Time
- Organizer
- Event Mode

If product behavior permits incomplete Draft creation, even fewer fields may be required initially, while publish-readiness shows missing requirements.

## 32.2 After Creation

Navigate to Event Setup.

Show setup checklist:

```text
Event Setup

✓ Basic Information
✓ Date & Time
○ Venue
○ Registration
○ Ticket Types
○ Agenda

[Preview]
[Publish Event]
```

## 32.3 Publish Readiness

Publish button behavior:

### Ready

Enabled.

### Not Ready

May remain visible but disabled or open a requirements panel.

Example:

```text
Before publishing:

• Add organizer name
• Set event start and end time
```

Never silently fail.

---

# 33. Attendee Management UX

## 33.1 Attendee List Priority

Recommended columns:

```text
Name
Email
Ticket Type
Registration Status
Check-In
Registered At
Actions
```

On narrower screens:

```text
Name
Ticket Type
Status
Check-In
```

## 33.2 Status Presentation

Example:

```text
Confirmed
Checked In
```

These are independent dimensions.

Do not combine them into one ambiguous badge such as `Active`.

## 33.3 Row Click

Clicking attendee name opens detail.

Avoid making the entire row an invisible click target if it interferes with selection/action controls.

## 33.4 Event-Day Mode

A simplified operational view may prioritize:

```text
Name
Ticket
Check-In State
[Check In]
```

---

# 34. Check-In UX Specification

## 34.1 Main Screen

Mobile-first:

```text
Event Name
CHECK-IN

[ Scan QR ]

or

Search attendee
[ Name, email, registration ID ]

Recent Check-Ins
```

## 34.2 QR Scanner

Scanner UI should show:

- camera preview,
- target frame,
- flashlight control only if browser/device permits and implementation is justified,
- `Use Manual Search` fallback.

Do not overload scanner screen with settings.

## 34.3 Feedback States

### Success

Large positive state.

### Duplicate

Large warning state with prior time.

### Invalid

Large error state.

### Wrong Event

Explicit:

```text
Ticket belongs to another event.
```

Do not label everything generically as invalid if the operator can act on the distinction.

## 34.4 Recovery

After result display:

```text
[Scan Next Ticket]
```

should be immediately available.

## 34.5 Recent Check-Ins

Show a short list so staff can confirm operation:

```text
10:42 Rina Wulandari
10:41 Budi Santoso
```

---

# 35. Reporting UX

## 35.1 Event Report Navigation

Recommended report landing:

```text
Event Report

[Registrations] [Ticket Types] [Attendance]

Date / Status filters where relevant

Summary Cards

Detailed Table

[Export CSV]
```

## 35.2 Report Priorities

Do not build a BI dashboard.

MVP should answer:

- How many registered?
- Which ticket types?
- How many attended?
- Who attended?
- What is attendance percentage?

## 35.3 Export

Export should reflect active filters when user selects filtered export.

Example menu:

```text
Export
• All attendees
• Current filtered results
```

---

# 36. Notification UX

## 36.1 Transactional Email Expectations

UI should communicate when email is expected.

Registration confirmation:

```text
Registration confirmed.
A confirmation email will be sent to rina@example.com.
```

Do not claim `Email sent` before delivery is confirmed if delivery is queued.

## 36.2 Admin Troubleshooting

If delivery status is exposed later:

```text
Confirmation Email
Queued / Sent / Failed
```

Do not make delivery debugging a primary attendee-facing concept.

---

# 37. Member Management UX

## 37.1 Member List

Columns:

```text
Name
Email
Role
Status
Joined
Actions
```

## 37.2 Invite Member

Short modal is appropriate:

```text
Invite Member

Email
Role

[Cancel] [Send Invitation]
```

## 37.3 Role Change

Use select + confirmation only when change materially affects access.

## 37.4 Last Owner Protection

If user tries to remove/demote last Owner:

```text
This role cannot be changed.

Every organization must have at least one Owner.
Assign another Owner first.
```

---

# 38. Component Inventory

Reusable UI components should include:

## Navigation

- AppSidebar
- MobileNavDrawer
- TopBar
- Breadcrumb
- EventTabs
- OrganizationSwitcher

## Data Display

- StatCard
- StatusBadge
- EmptyState
- DataTable
- ResponsiveEntityCard
- Pagination
- DefinitionList

## Forms

- TextInput
- Textarea
- Select
- Checkbox
- RadioGroup
- DateTimeInput
- FormError
- FormSection
- FileUpload
- CustomFieldEditor

## Feedback

- Toast
- Alert
- ConfirmationDialog
- Modal
- LoadingButton
- Skeleton
- CheckInResult

## Event Components

- EventHeader
- EventSetupChecklist
- TicketTypeCard
- AttendeeRow
- AttendanceSummary
- QRScanner
- TicketCard

The actual implementation may combine components when maintaining separate components would add needless complexity.

---

# 39. Blade / Livewire / Alpine UX Responsibility

## 39.1 Blade

Use for:

- layouts,
- server-rendered pages,
- shared partials/components,
- public event pages,
- semantic structure.

## 39.2 Livewire

Use where server-driven interaction improves UX:

- attendee search/filter,
- event forms,
- ticket type management,
- custom registration fields,
- dashboard updates,
- check-in workflows,
- settings.

## 39.3 Alpine.js

Use for local browser interaction:

- menu/dropdown state,
- modal transitions,
- collapsible sections,
- camera/scanner browser API coordination,
- small temporary UI state.

Do not duplicate authoritative business state inside Alpine.

## 39.4 Tailwind CSS

Use as a consistent design-system implementation layer.

Avoid arbitrary inline CSS unless a browser-specific feature requires it.

---

# 40. UX Acceptance Checklist

The UI/UX is ready for MVP implementation only when the following are true.

## Navigation

- [ ] Users can distinguish organization navigation from event navigation.
- [ ] Role-specific navigation does not expose irrelevant features.
- [ ] All protected actions remain server-authorized regardless of navigation visibility.

## Events

- [ ] Draft event setup clearly communicates missing publish requirements.
- [ ] Status is visible on all event detail pages.
- [ ] Event cancellation is visually unmistakable.
- [ ] Archive is distinct from delete.

## Registration

- [ ] Public attendee can register without creating an account.
- [ ] Required validation errors appear next to fields.
- [ ] Registration closed/scheduled/sold-out states are clear.
- [ ] Confirmation clearly provides registration/ticket access.

## Attendees

- [ ] Search explains searchable identifiers.
- [ ] Filters are visible and removable.
- [ ] Registration and attendance statuses are separate.
- [ ] Mobile attendee view remains usable.

## Check-In

- [ ] QR scanning is mobile-friendly.
- [ ] Manual search fallback exists.
- [ ] Success, duplicate, invalid, and wrong-event states are visually distinct.
- [ ] Scan-next action is fast.
- [ ] No unrelated administration distracts the operator.

## Reports

- [ ] Dashboard/report metrics use consistent labels.
- [ ] Empty reports do not produce errors.
- [ ] Export action is easy to find.
- [ ] Filtered export behavior is understandable.

## Accessibility

- [ ] Keyboard focus is visible.
- [ ] Forms use accessible labels.
- [ ] Status does not rely only on color.
- [ ] Dialog focus behavior is correct.
- [ ] Touch targets are usable on mobile.

## Commercial Polish

- [ ] Spacing and component rules are consistent.
- [ ] No empty future modules appear in navigation.
- [ ] No unnecessary charts or animations add visual noise.
- [ ] Demo mode can be visually distinguished if enabled later.
- [ ] Product feels coherent across admin, operations, and public attendee surfaces.

---

# Final UI/UX Direction

EventFlow should visually communicate:

> **organized, reliable, modern, focused, and operationally fast.**

It should not communicate:

> complex enterprise software, accounting ERP, social network, or ticket marketplace.

The recommended visual hierarchy is:

```text
Global Organization Workspace
        │
        ▼
Event Workspace
        │
        ├── Setup
        ├── Registration
        ├── Attendees
        ├── Check-In
        └── Reports

Public Event Experience
        │
        ├── Event Information
        ├── Registration
        ├── Confirmation
        └── Ticket / QR
```

The most important design rule is:

> **Every screen should help the user move the event forward.**

The interface shall remain visually simple enough for occasional organization users while being structured enough for professional Event Managers to operate multiple events repeatedly.

---

**End of Document**
