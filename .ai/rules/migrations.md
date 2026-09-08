---
paths:
  - 'app/Models/OrganizationSetting.php, database/migrations/**'
---

# Migrations

## organization_settings is extensible KV, core settings stay typed columns
organization_settings is the controlled extensible KV table (DATABASE.md §9): unique (organization_id, setting_key), setting_value is JSON cast to array. Core settings (name/timezone/locale/default_currency) stay first-class columns on organizations — do not move them into organization_settings. Only add keys for low-frequency, non-security-critical config (e.g. notifications.*).
