---
paths:
  - 'app/Livewire/Reports/**'
---

# Reports

## Dashboard aggregates via OrganizationDashboardQuery
The org dashboard (app/Queries/Dashboard/OrganizationDashboardQuery.php) owns all aggregation logic; the Livewire component only assigns query results to its own public props. Never pass view variables that shadow a public prop (Livewire merges public props into the view data and will override/blow up). Active = Published+Ongoing; upcoming = Published. Scope echoes EventIndex: Owner/Admin see all org events, everyone else only assigned events (DASH-001).
