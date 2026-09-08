---
paths:
  - 'app/Support/Demo/**, app/Actions/Organizations/**, app/Actions/Files/**, app/Policies/OrganizationPolicy.php'
---

# Files Policies

## Centralized demo-mode strategy through DemoMode/DemoGate
Demo mode is controlled solely by config demo.enabled (env DEMO_MODE) and resolved through App\Support\Demo\DemoMode + DemoGate. DemoMode::apply() (called in AppServiceProvider::boot) forces mail.default to log. The demo org is identified by the stable slug config demo.organization_slug ('demo-acme', set by DemoSeeder) — never rename it, or the reset command and DemoGate::denyOnDemoOrganization break. Restricted actions call DemoGate at the very top of their handle()/policy (RemoveMember, ChangeMemberRole, DeleteMediaFile, OrganizationPolicy::create). Do NOT add demo conditionals anywhere else. Reset = eventflow:demo:reset.
