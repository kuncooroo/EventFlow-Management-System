---
paths:
  - 'app/Actions/Reports/**, app/Http/Controllers/Reports/**'
---

# Controllers Reports

## CSV exports: explicit columns, chunked generator, no qr_token
CSV exports (ExportController + ExportCsv action) only emit explicit column sets; never select qr_token from tickets (ticket_code is allowed). Stream via id-paginated chunks (limit 500, registrations.id > lastId on a clone of the filtered query) so large exports keep bounded memory — do NOT yield inside a chunkById callback (it is not a generator). Column list for the attestation query is defined once; verified by nobody but the export generator. http download responses come from route app.reports.export?type=attendees|registrations|attendance with the same filter keys the report pages use (event_id, status, ticket_type_id, checked_in, registered_from, registered_to); attendees also requires event_id and re-authorizes ReportPolicy::view for that event.
