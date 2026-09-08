---
paths:
  - 'app/Actions/Organizations/**, app/Livewire/Organizations/**'
---

# Organizations

## Organization settings: authz, validation allowlists, audit key
UpdateOrganizationSettings gates via OrganizationPolicy manageSettings (Owner/Admin only; Staff 403). Validate timezone against \DateTimeZone::listIdentifiers(), locale against config('app.supported_locales'), default_currency as nullable alpha size:3 (uppercased before save). Audit any actual change as `organization.settings_changed`, subject_type `organization`, subject_id org id; skip the audit when nothing changed. Settings page route app.settings.index resolves org from OrganizationContext and is 403 for non-Owner/Admin.
